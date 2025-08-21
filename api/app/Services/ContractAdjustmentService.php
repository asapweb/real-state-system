<?php

namespace App\Services;

use App\Models\ContractAdjustment;
use App\Models\Contract;
use App\Models\IndexType;
use App\Models\IndexValue;
use App\Enums\ContractAdjustmentType;
use App\Enums\CalculationMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContractAdjustmentService
{
    private const DEFAULT_MAX_INDEX_AGE_DAYS = 15;
    private int $maxIndexAgeDays;

    public function __construct()
    {
        $this->maxIndexAgeDays = (int) config(
            'contracts.index_max_age_days',
            env('INDEX_MAX_AGE_DAYS', self::DEFAULT_MAX_INDEX_AGE_DAYS)
        );
    }

    /**
     * Aplica el ajuste al contrato (index/percentage/fixed/negotiated).
     * Congela base_amount al aplicar.
     */
    public function apply(ContractAdjustment $adjustment): void
    {
        Log::info('🚀 Iniciando aplicación de ajuste', [
            'adjustment_id'  => $adjustment->id,
            'type'           => $adjustment->type,
            'value'          => $adjustment->value,
            'factor'         => $adjustment->factor ?? null,
            'base_amount'    => $adjustment->base_amount ?? null,
            'contract_id'    => $adjustment->contract_id,
        ]);

        if ($adjustment->applied_at) {
            throw ValidationException::withMessages(['adjustment' => 'El ajuste ya fue aplicado.']);
        }
        if (is_null($adjustment->value)) {
            throw ValidationException::withMessages(['adjustment' => 'El ajuste no tiene un valor asignado.']);
        }

        $contract = $adjustment->contract;
        if (!$contract) {
            throw ValidationException::withMessages(['adjustment' => 'No se encontró el contrato asociado al ajuste.']);
        }

        // ⚠️ Para índices NO acumulativos: exigir aplicar en orden.
        // Si hay un ajuste previo (por fecha efectiva) del mismo índice SIN aplicar, no permitimos aplicar este.
        if ($adjustment->type === ContractAdjustmentType::INDEX) {
            $indexType = $adjustment->indexType;
            if ($indexType && !$indexType->isCumulative()) {
                $prevUnapplied = ContractAdjustment::query()
                    ->where('contract_id', $adjustment->contract_id)
                    ->where('type', ContractAdjustmentType::INDEX)
                    ->where('index_type_id', $adjustment->index_type_id)
                    ->where('effective_date', '<', $adjustment->effective_date)
                    ->whereNull('applied_at')
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if ($prevUnapplied) {
                    throw ValidationException::withMessages([
                        'adjustment' => sprintf(
                            'Debe aplicar primero el ajuste previo del %s (ID #%d) para mantener la cadena de base correcta.',
                            optional($prevUnapplied->effective_date)->toDateString(),
                            $prevUnapplied->id
                        ),
                    ]);
                }
            }
        }

        // 🔥 Congelar base_amount definitiva en el momento de aplicar
        $indexType  = $adjustment->indexType;
        $baseAmount = $this->resolveBaseAmountForApplication($contract, $adjustment, $indexType);

        // Calcular monto ajustado
        $appliedAmount = match ($adjustment->type) {
            ContractAdjustmentType::INDEX      => $this->applyIndexAdjustment($adjustment->fill(['base_amount' => $baseAmount])),
            ContractAdjustmentType::PERCENTAGE => round($baseAmount * (1 + $adjustment->value / 100), 2),
            ContractAdjustmentType::FIXED,
            ContractAdjustmentType::NEGOTIATED => round($adjustment->value, 2),
            default => throw ValidationException::withMessages([
                'adjustment' => 'Tipo de ajuste no soportado: ' . $adjustment->type
            ]),
        };

        // 💾 Persistir snapshot
        DB::transaction(function () use ($adjustment, $baseAmount, $appliedAmount) {
            $adjustment->update([
                'base_amount'    => round($baseAmount, 2),
                'applied_amount' => $appliedAmount,
                'applied_at'     => now(),
            ]);
        });

        Log::info('✅ Ajuste aplicado', [
            'adjustment_id' => $adjustment->id,
            'base_amount'   => $baseAmount,
            'final_amount'  => $appliedAmount,
        ]);
    }

    /**
     * Prepara factor y auditoría de índice.
     * No congela base_amount.
     */
    public function assignIndexValue(ContractAdjustment $adjustment): void
    {
        Log::info('🔍 Iniciando assignIndexValue', [
            'adjustment_id'  => $adjustment->id,
            'type'           => $adjustment->type,
            'effective_date' => optional($adjustment->effective_date)->toDateString(),
            'index_type_id'  => $adjustment->index_type_id,
        ]);

        if ($adjustment->type !== ContractAdjustmentType::INDEX) {
            throw ValidationException::withMessages(['index' => 'El ajuste no es de tipo índice.']);
        }
        if (!is_null($adjustment->value)) {
            throw ValidationException::withMessages(['index' => 'El ajuste ya tiene un valor asignado.']);
        }
        if (!is_null($adjustment->applied_at)) {
            throw ValidationException::withMessages(['index' => 'El ajuste ya fue aplicado y no puede modificarse.']);
        }

        $indexType = $adjustment->indexType;
        if (!$indexType) {
            throw ValidationException::withMessages(['index' => 'El ajuste no tiene un tipo de índice válido.']);
        }

        if ($indexType->calculation_mode === CalculationMode::RATIO) {
            [$percentage, $ratio, $Sdate, $Fdate, $Sval, $Fval] =
                $this->calculateRatioWithSnapshot($adjustment, $adjustment->contract);

            $adjustment->value          = $percentage;
            $adjustment->factor         = $ratio;
            $adjustment->index_S_date   = $Sdate;
            $adjustment->index_F_date   = $Fdate;
            $adjustment->index_S_value  = $Sval;
            $adjustment->index_F_value  = $Fval;

        } elseif ($indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {
            $coef = $this->calculateMultiplicativeChain($adjustment, $adjustment->contract);
            if (is_null($coef)) {
                throw ValidationException::withMessages(['index' => 'No se pudo calcular el coeficiente multiplicativo.']);
            }
            $adjustment->value  = $coef;
            $adjustment->factor = (float) $coef;
        } else {
            throw ValidationException::withMessages(['index' => 'El tipo de cálculo del índice no está soportado.']);
        }

        $adjustment->save();
    }

    /**
     * Calcula el importe ajustado para un índice (usa snapshot).
     */
    private function applyIndexAdjustment(ContractAdjustment $adjustment): float
    {
        if ($adjustment->base_amount === null) {
            throw ValidationException::withMessages([
                'adjustment' => 'No se definió base_amount al aplicar el ajuste.'
            ]);
        }
        if ($adjustment->factor === null) {
            throw ValidationException::withMessages([
                'adjustment' => 'No se definió factor al aplicar el ajuste.'
            ]);
        }

        $applied = round(((float) $adjustment->base_amount) * ((float) $adjustment->factor), 2);

        Log::info('🧮 Calculando ajuste INDEX', [
            'base_amount' => $adjustment->base_amount,
            'factor'      => $adjustment->factor,
            'result'      => $applied,
        ]);

        return $applied;
    }

    /**
     * Determina la base monetaria al momento de CALCULAR el valor (snapshot).
     */
    private function resolveBaseAmountForAssignment(Contract $contract, ContractAdjustment $adjustment, IndexType $indexType): float
    {
        if ($indexType->isCumulative()) {
            // Desde inicio
            return $contract->index_base_amount
                ?? $contract->initial_monthly_amount
                ?? $contract->monthly_amount;
        }

        // No acumulativo: encadenar sobre el último ajuste aplicado
        $prevAdj = ContractAdjustment::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('applied_at')
            ->where('effective_date', '<', $adjustment->effective_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $prevAdj?->applied_amount ?? $contract->monthly_amount;
    }

    /**
     * Determina la base monetaria al momento de APLICAR (por compatibilidad si no hay snapshot).
     */
    private function resolveBaseAmountForApplication(Contract $contract, ContractAdjustment $adjustment, IndexType $indexType): float
    {
        if ($indexType->isCumulative()) {
            return $contract->index_base_amount
                ?? $contract->initial_monthly_amount
                ?? $contract->monthly_amount;
        }

        $prevAdj = ContractAdjustment::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('applied_at')
            ->where('effective_date', '<', $adjustment->effective_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $prevAdj?->applied_amount ?? $contract->monthly_amount;
    }

    /**
     * Calcula % (para UI) y ratio exacto I(F)/I(S) con snapshot de fechas y valores.
     * - Mensual: F = mes anterior al ajuste, S según is_cumulative (inicio contrato) o último ajuste (mes anterior).
     * - Diario:  F = día anterior al ajuste; S = día de inicio (o día anterior al último ajuste), según is_cumulative.
     *
     * @return array [percentage (rounded), ratio, Sdate, Fdate, Sval, Fval]
     */
    private function calculateRatioWithSnapshot(ContractAdjustment $adjustment, Contract $contract): array
    {
        $indexType     = IndexType::find($adjustment->index_type_id);
        $effectiveDate = $adjustment->effective_date->copy();

        // F
        if ($indexType->isMonthlyFrequency()) {
            $Fdate = $effectiveDate->copy()->subMonthNoOverflow()->startOfMonth(); // mes anterior
        } else {
            $Fdate = $effectiveDate->copy()->subDay(); // día anterior
        }

        // S
        if ($indexType->isCumulative()) {
            $Sdate = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
            $Sdate = $indexType->isMonthlyFrequency()
                ? $Sdate->startOfMonth()
                : $Sdate; // diarios: día exacto de inicio
        } else {
            $prev = ContractAdjustment::query()
                ->where('contract_id', $contract->id)
                ->where('type', ContractAdjustmentType::INDEX)
                ->where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', '<', $effectiveDate)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($prev) {
                $Sdate = $prev->effective_date->copy();
                $Sdate = $indexType->isMonthlyFrequency()
                    ? $Sdate->subMonthNoOverflow()->startOfMonth() // mes anterior al último ajuste
                    : $Sdate->subDay(); // diarios: día anterior al último ajuste
            } else {
                $Sdate = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
                $Sdate = $indexType->isMonthlyFrequency() ? $Sdate->startOfMonth() : $Sdate;
            }
        }

        // I(S) e I(F)
        $Sval = $this->getIndexValueAtOrBefore($indexType->id, $Sdate)?->value;
        $Fval = $this->getIndexValueAtOrBefore($indexType->id, $Fdate)?->value;

        if ($Sval === null || $Fval === null) {
            throw ValidationException::withMessages(['index' => 'Faltan valores de índice para S o F.']);
        }

        $ratio      = (float) $Fval / (float) $Sval;
        $percentage = ($ratio - 1.0) * 100.0;

        return [
            round($percentage, 4),
            $ratio,
            $Sdate->toDateString(),
            $Fdate->toDateString(),
            (float) $Sval,
            (float) $Fval,
        ];
    }

    /**
     * Coeficiente multiplicativo encadenado (solo si tu IndexValue.value ya es coeficiente por período).
     * Si en tu base guardás NIVELES, recomendación: usar RATIO (I(F)/I(S)).
     */
    private function calculateMultiplicativeChain(ContractAdjustment $adjustment, Contract $contract): ?float
    {
        $indexType     = IndexType::find($adjustment->index_type_id);
        $effectiveDate = $adjustment->effective_date->copy();

        // Definir S y F como en ratio (para armar la lista de períodos)
        if ($indexType->isMonthlyFrequency()) {
            $F = $effectiveDate->copy()->subMonthNoOverflow()->startOfMonth();
        } else {
            $F = $effectiveDate->copy()->subDay();
        }

        if ($indexType->isCumulative()) {
            $S = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
            $S = $indexType->isMonthlyFrequency() ? $S->startOfMonth() : $S;
        } else {
            $prev = ContractAdjustment::query()
                ->where('contract_id', $contract->id)
                ->where('type', ContractAdjustmentType::INDEX)
                ->where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', '<', $effectiveDate)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($prev) {
                $S = $prev->effective_date->copy();
                $S = $indexType->isMonthlyFrequency()
                    ? $S->subMonthNoOverflow()->startOfMonth()
                    : $S->subDay();
            } else {
                $S = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
                $S = $indexType->isMonthlyFrequency() ? $S->startOfMonth() : $S;
            }
        }

        // Generar lista de períodos a encadenar: (S+1 … F)
        $periods = $indexType->isMonthlyFrequency()
            ? $this->generateMonthlyPeriods($S, $F, true)    // excluye S (arranca en S+1)
            : $this->generateDailyPeriods($S, $F, true);     // excluye S (arranca en S+1 día)

        if (empty($periods)) {
            Log::warning('⚠️ No hay períodos generados para CHAIN', ['S' => $S, 'F' => $F]);
            return null;
        }

        // Traer coeficientes EXACTOS (se asume IndexValue.value es el coeficiente del período)
        $values = IndexValue::query()
            ->where('index_type_id', $indexType->id)
            ->whereIn('effective_date', $periods)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'asc')
            ->pluck('value')
            ->all();

        // Si no guardás coeficientes per se, te conviene RATIO. Aquí mantenemos compatibilidad.
        if (count($values) !== count($periods)) {
            Log::warning('⚠️ Faltan coeficientes para CHAIN', [
                'expected' => $periods,
                'found'    => count($values),
            ]);
            return null;
        }

        // Validación de antigüedad para diarios
        if ($indexType->isDailyFrequency()) {
            $lastIndexDate = end($periods);
            $daysDiff = Carbon::parse($lastIndexDate)->diffInDays($F);
            if ($daysDiff > $this->maxIndexAgeDays) {
                Log::warning('⚠️ Valor diario demasiado antiguo para ajuste', [
                    'last_index_date' => $lastIndexDate,
                    'effective_date'  => $effectiveDate->toDateString(),
                    'days_diff'       => $daysDiff,
                ]);
                return null;
            }
        }

        // Producto de coeficientes
        $coef = array_reduce($values, fn($carry, $v) => $carry * (float)$v, 1.0);
        return $coef;
    }

    /**
     * Devuelve el último IndexValue.value con fecha <= $at
     */
    private function getIndexValueAtOrBefore(int $indexTypeId, Carbon $at): ?IndexValue
    {
        return IndexValue::query()
            ->where('index_type_id', $indexTypeId)
            ->where('effective_date', '<=', $at->toDateString())
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Genera fechas (primer día de cada mes) desde S+offset hasta F.
     * Si $excludeStart es true, arranca en S+1 mes.
     */
    private function generateMonthlyPeriods(Carbon $S, Carbon $F, bool $excludeStart = true): array
    {
        $current = $excludeStart ? $S->copy()->startOfMonth()->addMonth() : $S->copy()->startOfMonth();
        $end     = $F->copy()->startOfMonth();

        $out = [];
        while ($current->lte($end)) {
            $out[] = $current->toDateString(); // 'Y-m-d' (día 1)
            $current->addMonth();
        }
        return $out;
    }

    /**
     * Genera fechas diarias desde S+offset hasta F (inclusive).
     * Si $excludeStart es true, arranca en S+1 día.
     */
    private function generateDailyPeriods(Carbon $S, Carbon $F, bool $excludeStart = true): array
    {
        $current = $excludeStart ? $S->copy()->addDay() : $S->copy();
        $end     = $F->copy();

        $out = [];
        while ($current->lte($end)) {
            $out[] = $current->toDateString(); // 'Y-m-d'
            $current->addDay();
        }
        return $out;
    }
}
