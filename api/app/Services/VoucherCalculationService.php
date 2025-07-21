<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherItem;
use Illuminate\Validation\ValidationException;

class VoucherCalculationService
{
    /**
     * Calcula subtotal, IVA y total para un ítem individual (no lo persiste)
     */
    public function calculateItem(VoucherItem $item): VoucherItem
    {
        $item->subtotal = $item->quantity * $item->unit_price;

        if ($item->taxRate && $item->taxRate->rate > 0) {
            $item->vat_amount = round($item->subtotal * ($item->taxRate->rate / 100), 2);
        } else {
            $item->vat_amount = 0;
        }

        $item->subtotal_with_vat = $item->subtotal + $item->vat_amount;

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
        $total = 0;

        $items = $voucher->items ?? collect();

        foreach ($items as $item) {
            $this->calculateItem($item);

            if (!$item->taxRate) {
                $subtotal_untaxed += $item->subtotal;
                $total += $item->subtotal_with_vat;
                continue;
            }

            switch ($item->taxRate->id) {
                case 1: // Exento
                    $subtotal_exempt += $item->subtotal;
                    break;
                case 2: // No Gravado
                    $subtotal_untaxed += $item->subtotal;
                    break;
                default:
                    if ($item->taxRate->included_in_vat_detail) {
                        $subtotal_taxed += $item->subtotal;
                        $subtotal_vat += $item->vat_amount;
                    } else {
                        $subtotal_untaxed += $item->subtotal;
                    }
                    break;
            }

            $total += $item->subtotal_with_vat;
        }

        $voucher->subtotal_exempt = $subtotal_exempt;
        $voucher->subtotal_untaxed = $subtotal_untaxed;
        $voucher->subtotal_taxed = $subtotal_taxed;
        $voucher->subtotal_vat = $subtotal_vat;
        $voucher->subtotal_other_taxes = 0;
        $voucher->total = $total;
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
        $voucher->total = $payments->sum('amount');
    }
}
