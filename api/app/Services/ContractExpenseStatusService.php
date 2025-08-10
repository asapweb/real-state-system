<?php

namespace App\Services;

use App\Models\ContractExpense;
use App\Enums\ContractExpenseStatus;
use Illuminate\Validation\ValidationException;

class ContractExpenseStatusService
{
    /**
     * Cambia el estado de un gasto de contrato validando reglas.
     */
    public function changeStatus(ContractExpense $expense, ContractExpenseStatus $targetStatus): ContractExpense
    {
        $current = $expense->status;

        // ❌ No se puede cambiar un gasto bloqueado
        if ($expense->is_locked && $current !== $targetStatus) {
            throw ValidationException::withMessages([
                'status' => "No se puede modificar un gasto bloqueado ({$current->value}).",
            ]);
        }

        // Validar transiciones según estado actual
        $this->validateTransition($expense, $targetStatus);

        // Aplicar cambio
        $expense->status = $targetStatus;
        $expense->save();

        return $expense;
    }

    /**
     * Valida las transiciones permitidas de estado.
     */
    protected function validateTransition(ContractExpense $expense, ContractExpenseStatus $targetStatus): void
    {
        $current = $expense->status;

        // 🔒 Estados terminales
        if (in_array($current, [ContractExpenseStatus::BILLED, ContractExpenseStatus::CREDITED, ContractExpenseStatus::LIQUIDATED]) 
            && $current !== $targetStatus) {
            throw ValidationException::withMessages([
                'status' => "No se puede cambiar un gasto ya {$current->value}.",
            ]);
        }

        // Transiciones válidas
        $allowed = match ($current) {
            ContractExpenseStatus::PENDING => $this->allowedFromPending($expense),
            ContractExpenseStatus::CREDITED => [ContractExpenseStatus::LIQUIDATED],
            default => [],
        };

        if (! in_array($targetStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "No se permite cambiar de {$current->value} a {$targetStatus->value}.",
            ]);
        }
    }

    /**
     * Define transiciones válidas desde PENDING.
     */
    protected function allowedFromPending(ContractExpense $expense): array
    {
        return match (true) {
            // tenant → tenant: solo validar pago, nunca facturar
            $expense->paid_by->isTenant() && $expense->responsible_party->isTenant()
                => [ContractExpenseStatus::VALIDATED],

            // tenant → owner: NC (luego liquidación)
            $expense->paid_by->isTenant() && $expense->responsible_party->isOwner()
                => [ContractExpenseStatus::CREDITED],

            // owner → tenant: facturar directo
            $expense->paid_by->isOwner() && $expense->responsible_party->isTenant()
                => [ContractExpenseStatus::BILLED],

            // owner → owner: liquidar directo
            $expense->paid_by->isOwner() && $expense->responsible_party->isOwner()
                => [ContractExpenseStatus::LIQUIDATED],

            // agency → tenant: facturar directo
            $expense->paid_by->isAgency() && $expense->responsible_party->isTenant()
                => [ContractExpenseStatus::BILLED],

            // agency → owner: liquidar directo
            $expense->paid_by->isAgency() && $expense->responsible_party->isOwner()
                => [ContractExpenseStatus::LIQUIDATED],

            default => [],
        };
    }
}
