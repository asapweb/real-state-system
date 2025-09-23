<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAdjustment;
use Carbon\Carbon;

class ContractRentCalculator
{
    /**
     * Devuelve la base mensual del contrato para el período P:
     * - Prioriza el último ajuste VIGENTE con `applied_amount` (<= inicio de P).
     * - Si no hay, cae a `contracts.monthly_amount`.
     */
    public function monthlyBaseFor(Contract $contract, Carbon $period): float
    {
        $periodStart = $period->copy()->startOfMonth()->toDateString();

        /** @var ContractAdjustment|null $adj */
        $adj = $contract->adjustments()
            ->whereNotNull('applied_amount')
            ->whereDate('effective_date', '<=', $periodStart)
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($adj && $adj->applied_amount !== null) {
            return (float) $adj->applied_amount;
        }

        // Fallback: monto mensual actual del contrato
        return (float) $contract->monthly_amount;
    }

    /**
     * Aplica prorrateo del primer/último mes si corresponde según flags del contrato.
     */
    public function applyProrationIfNeeded(Contract $contract, Carbon $period, float $base): float
    {
        $monthStart = $period->copy()->startOfMonth();
        $monthEnd   = $period->copy()->endOfMonth();

        $contractStart = $contract->start_date ? Carbon::parse($contract->start_date) : $monthStart;
        $contractEnd   = $contract->end_date ? Carbon::parse($contract->end_date) : null;

        // Intersección de vigencias
        $from = $contractStart->greaterThan($monthStart) ? $contractStart : $monthStart;
        $to   = $contractEnd && $contractEnd->lessThan($monthEnd) ? $contractEnd : $monthEnd;

        $daysInMonth   = (int) $monthStart->daysInMonth;
        $effectiveDays = $from->gt($to) ? 0 : $from->diffInDays($to) + 1;

        $prorateFirst = $contract->prorate_first_month && $monthStart->isSameMonth($contractStart);
        $prorateLast  = $contract->prorate_last_month && $contractEnd && $monthStart->isSameMonth($contractEnd);

        $needsProration = $prorateFirst || $prorateLast;

        if ($needsProration && $effectiveDays > 0 && $effectiveDays < $daysInMonth) {
            return round($base * ($effectiveDays / $daysInMonth), 2);
        }

        return round($base, 2);
    }
}
