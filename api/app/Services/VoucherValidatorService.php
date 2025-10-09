<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de validación de comprobantes antes de su emisión.
 *
 * Este validador verifica que los comprobantes cumplan con todas las reglas
 * funcionales y contables antes de ser emitidos. El proceso incluye:
 * - Recalcular totales (según tipo)
 * - Validar estructura y lógica según tipo de comprobante (FAC, COB, RCB, N/C, etc.)
 * - Lanzar excepciones claras en caso de errores.
 */
class VoucherValidatorService
{
    public function __construct(
        protected VoucherCalculationService $calculationService,
    ) {}

    /**
     * Punto de entrada principal: valida un comprobante antes de su emisión.
     */
    public function validateBeforeIssue(Voucher $voucher): void
    {
        if ($voucher->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden emitir comprobantes en estado borrador.',
            ]);
        }

        // Dentro de validateBeforeIssue(Voucher $voucher)
        $type = $voucher->booklet?->voucherType;
        $isLqi = ($type?->code === 'LQI') || (strtoupper($type?->short_name ?? '') === 'LIQ');

        if ($isLqi) {
            // Debe tener items
            if (!$voucher->items || $voucher->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'La LQI no tiene ítems elegibles.']);
            }

            // Todos los items deben tener contract_charge_id
            if ($voucher->items->contains(fn($it) => empty($it->contract_charge_id))) {
                throw ValidationException::withMessages(['items' => 'Todos los ítems de LQI deben referenciar un cargo.']);
            }

            // Moneda uniforme (los cargos ya la garantizan; adicional por seguridad)
            // (Si tenés currency por ítem, chequealo; si no, omití esto)

            // Prohibir ítems subtract en comprobantes NO LQI/LQP
            // (esta regla inversa ponela en el else de tipos tradicionales)
        }


        // Recalcular totales
        $this->calculationService->calculateVoucher($voucher);

        $type = strtoupper($voucher->voucher_type_short_name);

        // Validar saltos en la numeración
        $this->validateNoMissingPreviousVouchers($voucher);

        // Prohibir subtract fuera de LQI/LQP
        $isLqiOrLqp = in_array($type, ['LQI','LQP'], true);
        if (!$isLqiOrLqp && $voucher->items->contains(fn($it) => ($it->impact ?? 'add') === 'subtract')) {
            throw ValidationException::withMessages([
                'items' => 'Este tipo de comprobante no admite ítems con impacto negativo.',
            ]);
        }

        // Validar fecha de emisión
        $this->validateNoEarlierIssueDate($voucher);

        match ($type) {
            'FAC', 'COB' => $this->validateItemsRequired($voucher),
            'LIQ', 'LQP' => $this->validateItemsRequired($voucher),
            'N/D' => $this->validateDebitNote($voucher),
            'N/C' => $this->validateCreditNote($voucher),
            'RCB', 'RPG' => $this->validateReceipt($voucher),
            default => null,
        };
    }


    protected function validateNoEarlierIssueDate(Voucher $voucher): void
    {
        $comprobantePosterior = Voucher::where('booklet_id', $voucher->booklet_id)
            ->where('status', 'issued')
            ->where('issue_date', '>', $voucher->issue_date)
            ->exists();

        if ($comprobantePosterior) {
            throw ValidationException::withMessages([
                'issue_date' => 'La fecha de emisión no puede ser anterior a la de otro comprobante ya emitido en este talonario.',
            ]);
        }
    }

    /**
     * Valida que no haya saltos en la numeración de comprobantes fiscales.
     */
    protected function validateNoMissingPreviousVouchers(Voucher $voucher): void
    {
        // Solo aplica a comprobantes fiscales
        if (!in_array($voucher->voucher_type_short_name, ['FAC', 'N/C', 'N/D'])) {
            return;
        }

        $haySaltos = Voucher::where('booklet_id', $voucher->booklet_id)
            ->where('number', '<', $voucher->number)
            ->where('status', '!=', 'issued')
            ->exists();

        if ($haySaltos) {
            throw ValidationException::withMessages([
                'number' => 'No se puede emitir este comprobante porque existen comprobantes anteriores sin emitir en el talonario.',
            ]);
        }
    }


    /**
     * Valida comprobantes que requieren ítems y total positivo (FAC, COB, LIQ, N/D).
     * También valida la cantidad de asociaciones en caso de nota de débito.
     */
    protected function validateItemsRequired(Voucher $voucher): void
    {
        $tolerance = 0.01;

        if ($voucher->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Debe incluir al menos un ítem.',
            ]);
        }

        if (abs((float) $voucher->total) < $tolerance) {
            throw ValidationException::withMessages([
                'total' => 'El total debe ser mayor a cero.',
            ]);
        }
    }

    protected function validateDebitNote(Voucher $voucher): void
    {
        // ✅ Debe tener ítems y total > 0
        $this->validateItemsRequired($voucher);

        \Log::info('voucher->associations', ['voucher' => $voucher->associations]);
        // ✅ Debe tener exactamente una asociación (a una factura)
        if ($voucher->associations->count() !== 1) {
            throw ValidationException::withMessages([
                'associated_voucher_ids' => 'La nota de débito debe estar asociada a un único comprobante.',
            ]);
        }

         // ❌ No puede asociarse a NC, RCB, RPG, ND (solo factura)
         foreach ($voucher->associations as $assoc) {
            $target = $assoc->associatedVoucher;
            if (!$target) continue;

            $forbidden = ['N/C', 'RCB', 'RPG', 'N/D'];
            if (in_array(strtoupper($target->voucher_type_short_name), $forbidden)) {
                throw ValidationException::withMessages([
                    'associated_voucher_ids' => "No se puede asociar a comprobantes de tipo {$target->voucher_type_short_name} ({$target->full_number}).",
                ]);
            }
        }

        // ❌ No se permiten pagos en una nota de débito
        if ($voucher->payments->isNotEmpty()) {
            throw ValidationException::withMessages([
                'payments' => 'Las notas de débito no deben contener pagos.',
            ]);
        }
    }


    protected function validateCreditNote(Voucher $voucher): void
    {
        // ✅ Debe tener ítems y total > 0
        $this->validateItemsRequired($voucher);

        // ❌ No se permiten pagos en una nota de crédito
        if ($voucher->payments->isNotEmpty()) {
            throw ValidationException::withMessages([
                'payments' => 'Las notas de crédito no deben contener pagos.',
            ]);
        }

        // ❌ No puede asociarse a otras NC, RCB, RPG
        foreach ($voucher->associations as $assoc) {
            $target = $assoc->associatedVoucher;
            if (!$target) continue;

            if ($target->voucherType->credit) {
                throw ValidationException::withMessages([
                    'associated_voucher_ids' => "No se puede asociar a comprobantes de tipo crédito ({$target->voucher_type_short_name} - {$target->full_number}).",
                ]);
            }
        }

    }



    /**
     * Valida un recibo (RCB o RPG) antes de emitirse.
     * - Si tiene total > 0: debe tener pagos y coincidir con el total.
     * - Si el total es 0: debe compensar perfectamente ítems y aplicaciones.
     * - No puede aplicarse a otros recibos.
     */
    protected function validateReceipt(Voucher $voucher): void
    {
        $sumPayments = $voucher->payments->sum('amount');
        $sumApplications = $voucher->applications->sum(function ($app) {
            $target = $app->appliedTo ?? $app->appliedVoucher ?? null;
            $sign = ($target && $target->voucherType->credit) ? -1 : 1;
            return $sign * $app->amount;
        });
        $sumItems = $voucher->items->sum(fn ($i) => $i->quantity * $i->unit_price);
        $sumItemsAndApplications = $sumItems + $sumApplications;
        $tolerance = 0.01;

        // ❌ No se permite aplicar un recibo a otro recibo
        foreach ($voucher->applications as $app) {
            $target = $app->appliedTo ?? $app->appliedVoucher ?? null;
            if ($target && in_array(strtoupper($target->voucher_type_short_name), ['RCB', 'RPG'])) {
                throw ValidationException::withMessages([
                    'applications' => 'No se puede aplicar un recibo a otro recibo.',
                ]);
            }
        }

        // ✅ Caso 1: total > 0
        if ((float) $voucher->total > $tolerance) {
            if ($sumPayments <= 0) {
                throw ValidationException::withMessages([
                    'payments' => 'Debe registrar al menos una forma de pago si el total es mayor a cero.',
                ]);
            }

            // ⚠ Validar moneda de cada cuenta contra la moneda del voucher
            foreach ($voucher->payments as $payment) {
                $cashAccount = $payment->cashAccount;
                if ($cashAccount && $cashAccount->currency !== $voucher->currency) {
                    throw ValidationException::withMessages([
                        'payments' => "La cuenta seleccionada para el pago '{$cashAccount->name}' no corresponde a la moneda del comprobante ({$voucher->currency}).",
                    ]);
                }
            }


            if (abs((float) $sumItemsAndApplications - $sumPayments) > $tolerance) {
                throw ValidationException::withMessages([
                    'payments' => 'El total del recibo no coincide con la suma de los pagos.',
                ]);
            }

            return;
        }

        // ✅ Caso 2: total = 0 (compensación exacta con comprobantes)
        if (abs((float) $voucher->total) <= $tolerance) {
            if ($voucher->items->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'No se deben registrar ítems en un recibo de monto cero.',
                ]);
            }

            if ($voucher->payments->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'payments' => 'No se deben registrar pagos en un recibo de monto cero.',
                ]);
            }

            if ($voucher->applications->isEmpty()) {
                throw ValidationException::withMessages([
                    'applications' => 'Debe registrar al menos una aplicación para un recibo de monto cero.',
                ]);
            }

            if (abs($sumApplications) > $tolerance) {
                throw ValidationException::withMessages([
                    'total' => 'Las aplicaciones no compensan exactamente el total del recibo.',
                ]);
            }

            return;
        }
    }
}
