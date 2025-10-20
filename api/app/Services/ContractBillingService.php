<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Models\Contract;
use App\Models\ContractExpense;
use App\Models\Voucher;
use App\Exceptions\PendingAdjustmentException;
use Carbon\Carbon;

class ContractBillingService
{
    /**
     * Obtiene un resumen de cobranza (renta + gastos) para un contrato en un período dado.
     *
     * @param  Contract       $contract
     * @param  Carbon|string  $period
     * @return array
     *
     * [
     *   'rent' => 150000.00,
     *   'expenses' => [
     *       ['currency' => 'ARS', 'amount' => 25000.00],
     *       ['currency' => 'USD', 'amount' => 150.00],
     *   ],
     *   'pending_adjustment' => true|false
     * ]
     */
    public function getBillingPreview(Contract $contract, Carbon|string $period): array
    {
        $period = normalizePeriodOrFail($period);

        // Inicializar variables
        $pendingAdjustment = false;
        $rentAmount = null;

        // Cálculo de renta con detección de ajuste pendiente
        try {
            $rentAmount = $contract->calculateRentForPeriod($period);
        } catch (PendingAdjustmentException $e) {
            $pendingAdjustment = true;
        }

        // Cálculo de gastos agrupados por moneda
        $expenses = $this->calculateExpensesByCurrency($contract, $period);

        return [
            'rent' => $rentAmount,
            'expenses' => $expenses,
            'pending_adjustment' => $pendingAdjustment,
        ];
    }

    /**
     * Calcula los gastos de un contrato agrupados por moneda,
     * incluyendo información de bloqueo y voucher.
     */
    protected function calculateExpensesByCurrency(Contract $contract, Carbon $period): array
    {
        $expenses = $contract->expenses()
            ->whereMonth('effective_date', $period->month)
            ->whereYear('effective_date', $period->year)
            ->whereIn('status', [
                \App\Enums\ContractExpenseStatus::PENDING,
                \App\Enums\ContractExpenseStatus::BILLED,
            ])
            ->where(function ($q) {
                $q->where(function ($q2) { 
                    $q2->where('paid_by', 'owner')->where('responsible_party', 'tenant');
                })
                ->orWhere(function ($q2) { 
                    $q2->where('paid_by', 'agency')->where('responsible_party', 'tenant');
                });
            })
            ->get();

        return $expenses->groupBy('currency')->map(function ($group) {
            return [
                'currency' => $group->first()->currency,
                'amount' => round($group->sum('amount'), 2),
                'expenses' => $group->map(function ($expense) use ($group) {
                    $locked = $expense->voucher_id !== null; // Bloqueado si está en un voucher
                    return [
                        'id' => $expense->id,
                        'description' => $expense->description ? $expense->description : 'Gasto ' . $expense->id,
                        'amount' => $expense->amount,
                        'status' => $expense->status->value,
                        'voucher_id' => $expense->voucher_id,
                        'included_in_voucher' => $expense->included_in_voucher,
                        'locked' => $locked,
                        'currency' => $group->first()->currency,
                        'state_label' => $locked
                            ? "Incluido en Voucher #{$expense->voucher_id}"
                            : 'Pendiente',
                    ];
                })->toArray(),
            ];
        })->values()->toArray();
    }


    public function determineStatus(Contract $contract, Carbon $period, bool $hasPendingAdjustment): string
    {
        if ($hasPendingAdjustment) {
            return 'pending_adjustment';
        }

        $vouchers = $contract->vouchers()
            ->whereDate('period', $period->toDateString())
            ->get();

        $hasDraft = $vouchers->filter(fn (Voucher $voucher) => $voucher->status === VoucherStatus::Draft)->isNotEmpty();
        $hasIssued = $vouchers->filter(fn (Voucher $voucher) => $voucher->status === VoucherStatus::Issued)->isNotEmpty();
        $unlinkedExpenses = $this->hasUnlinkedExpenses($contract, $period);

        if ($hasDraft || ($hasIssued && $unlinkedExpenses)) {
            return 'draft';
        }

        if ($hasIssued && !$unlinkedExpenses) {
            return 'issued';
        }

        return 'pending';
    }

    protected function hasUnlinkedExpenses(Contract $contract, Carbon $period): bool
    {
        return ContractExpense::query()
            ->where('contract_id', $contract->id)
            ->whereMonth('effective_date', $period->month)
            ->whereYear('effective_date', $period->year)
            ->whereNull('voucher_id')
            ->exists();
    }

}
