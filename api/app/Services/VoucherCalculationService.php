<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Enums\VoucherItemImpact;

use Illuminate\Validation\ValidationException;

class VoucherCalculationService
{
    /**
     * Calcula subtotal, IVA y total para un ítem individual (no lo persiste)
     */
    public function calculateItem(VoucherItem $item, $letter): VoucherItem
    {
        $item->subtotal = $item->quantity * $item->unit_price;
        $item->subtotal_with_vat = $item->subtotal;
        if ($item->taxRate && $item->taxRate->rate > 0) {
            if ($letter === 'B') {
                $item->vat_amount = round(
                    $item->subtotal * ($item->taxRate->rate / (100 + $item->taxRate->rate)),
                    2
                );
            } else {
                $item->vat_amount = round($item->subtotal * ($item->taxRate->rate / 100), 2);
                $item->subtotal_with_vat = $item->subtotal + $item->vat_amount;
            }
            // $item->vat_amount = round($item->subtotal * ($item->taxRate->rate / 100), 2);
        } else {
            $item->vat_amount = 0;
        }

        return $item;
    }

    /**
     * Calcula totales para un comprobante (sin persistir).
     * Según el tipo de comprobante, puede usar ítems, pagos y/o aplicaciones.
     */
    public function calculateVoucher(Voucher $voucher): void
    {
        $type = strtoupper($voucher->voucher_type_short_name);

        if (in_array($type, ['RCB', 'RPG'])) {
            $this->calculateFromPayments($voucher);
        } else {
            // Para FAC, COB, LIQ, N/D, N/C
            $this->calculateFromItems($voucher);
        }
    }

    /**
     * Calcula totales desde ítems (para FAC, COB, LIQ, N/D, etc.)
     */
    protected function calculateFromItems(Voucher $voucher): void
    {
        $subtotal_exempt = 0;
        $subtotal_untaxed = 0;
        $subtotal_taxed = 0;
        $subtotal_vat = 0;
        $subtotal_other_taxes = 0;
        $subtotal = 0;
        $total = 0;

        $items = $voucher->items ?? collect();

        foreach ($items as $item) {
            $this->calculateItem($item, $voucher->voucher_type_letter);

            // Determinar el signo según impact (string o PHP enum)
            // Los vouchers siempre son add y luego se aplica el signo en la cuenta corriente
            // $sign = $item->impact instanceof VoucherItemImpact
            // ? $item->impact->sign()
            // : (strtolower((string)($item->impact ?? 'add')) === 'subtract' ? -1 : 1);
            $sign = 1;

            // Subtotales "base"
            $subtotal += $sign * $item->subtotal;

            if (!$item->taxRate) {
                $subtotal_untaxed += $sign * $item->subtotal;
                $total += $sign * $item->subtotal_with_vat;
                continue;
            }

            switch ($item->taxRate->id) {
                case 1: // Exento
                    $subtotal_exempt += $sign * $item->subtotal;
                    break;
                case 2: // No Gravado
                    $subtotal_untaxed += $sign * $item->subtotal;
                    break;
                default:
                    if ($item->taxRate->included_in_vat_detail) {
                        if ($voucher->voucher_type_letter === 'B') {
                            $subtotal_taxed += $sign * ($item->subtotal - $item->vat_amount);
                            $subtotal_vat   += $sign * $item->vat_amount;
                        } else {
                            $subtotal_taxed += $sign * $item->subtotal;
                            $subtotal_vat   += $sign * $item->vat_amount;
                        }
                    } else {
                        $subtotal_untaxed += $sign * $item->subtotal;
                    }
                    break;
            }

            // Total siempre con IVA incluido (según tu lógica actual)
            $total += $sign * $item->subtotal_with_vat;
        }
        $voucher->subtotal_exempt = $subtotal_exempt;
        $voucher->subtotal_untaxed = $subtotal_untaxed;
        $voucher->subtotal_taxed = $subtotal_taxed;
        $voucher->subtotal_vat = $subtotal_vat;
        $voucher->subtotal_other_taxes = $subtotal_other_taxes;
        $voucher->subtotal = $subtotal;
        $voucher->total = $total;
        // throw ValidationException::withMessages([
        //     'items calculated!' => $voucher,
        // ]);
    }

    /**
     * Calcula totales desde ítems + aplicaciones (para N/C)
     */
    protected function calculateFromItemsAndApplications(Voucher $voucher): void
    {
        $this->calculateFromItems($voucher);

        $applications = $voucher->applications ?? collect();

        $sumApplications = $applications->sum(function ($app) {
            $target = $app->appliedTo ?? $app->appliedVoucher ?? null;
            $sign = ($target && $target->voucherType->credit) ? -1 : 1;
            return $sign * $app->amount;
        });

        $voucher->total += $sumApplications;
    }

    /**
     * Calcula el total desde payments (para RCB / RPG)
     */
    protected function calculateFromPayments(Voucher $voucher): void
    {
        $payments = $voucher->payments ?? collect();

        $voucher->subtotal_exempt = 0;
        $voucher->subtotal_untaxed = 0;
        $voucher->subtotal_taxed = 0;
        $voucher->subtotal_vat = 0;
        $voucher->subtotal_other_taxes = 0;
        $voucher->subtotal = 0;
        $voucher->total = $payments->sum('amount');
    }
}
