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
        DB::transaction(function () use ($adjustment) {

            // Re-cargar y bloquear el ajuste para esta transacción
            $adj = \App\Models\ContractAdjustment::query()
                ->whereKey($adjustment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Revalidaciones bajo lock
            if ($adj->applied_at) {
                throw ValidationException::withMessages(['adjustment' => 'El ajuste ya fue aplicado.']);
            }
            if (is_null($adj->value)) {
                throw ValidationException::withMessages(['adjustment' => 'El ajuste no tiene un valor asignado.']);
            }

            $contract = $adj->contract()->lockForUpdate()->first(); // opcional pero recomendable
            if (!$contract) {
                throw ValidationException::withMessages(['adjustment' => 'No se encontró el contrato asociado al ajuste.']);
            }

            $prevWhere = function ($q) use ($adj) {
                $q->where('effective_date', '<', $adj->effective_date)
                ->orWhere(function ($q2) use ($adj) {
                    $q2->whereDate('effective_date', '=', $adj->effective_date)
                        ->where('id', '<', $adj->id); // desempate por id
                });
            };

            $prevUnapplied = ContractAdjustment::query()
                ->where('contract_id', $adj->contract_id)
                ->whereNull('applied_at')
                ->where($prevWhere)
                ->lockForUpdate()
                ->orderBy('effective_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($prevUnapplied) {
                throw ValidationException::withMessages([
                    'adjustment' => sprintf(
                        'Hay ajustes anteriores sin aplicar (ej. #%d del %s). Debe aplicarlos antes.',
                        $prevUnapplied->id,
                        optional($prevUnapplied->effective_date)->toDateString()
                    ),
                ]);
            }

            // Determinar base_amount “congelado” al aplicar
            $indexType  = $adj->indexType; // puede ser null
            \Log::info('adj', ['adj' => $adj]);
            \Log::info('indexType', ['indexType' => $indexType]);
            $baseAmount = $this->resolveBaseAmountForApplication($contract, $adj, $indexType);

            // Calcular aplicado (con política monetaria centralizada)
            $appliedAmount = match ($adj->type) {
                ContractAdjustmentType::INDEX      => $this->applyIndexAdjustment($adj->fill(['base_amount' => $baseAmount])),
                ContractAdjustmentType::PERCENTAGE => money_round((float)$baseAmount * (1 + ((float)$adj->value / 100))),
                ContractAdjustmentType::FIXED,
                ContractAdjustmentType::NEGOTIATED => money_round((float)$adj->value),
                default => throw ValidationException::withMessages([
                    'adjustment' => 'Tipo de ajuste no soportado: ' . $adj->type
                ]),
            };

            // Persistir snapshot + marcar aplicado (usa el método de dominio)
            $adj->base_amount    = money_round($baseAmount);
            $adj->applied_amount = money_round($appliedAmount);
            // Si tu método permite timestamp custom, podrías pasar Carbon::now()
            $adj->markAsApplied(); // establece applied_at internamente y guarda
        });
    }

    /**
     * Prepara factor y auditoría de índice.
     * No congela base_amount.
     */
    public function assignIndexValue(ContractAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {

            // Bloquear el ajuste para esta transacción
            $adj = \App\Models\ContractAdjustment::query()
            ->whereKey($adjustment->getKey())
            ->lockForUpdate()
            ->firstOrFail();

            if ($adj->type !== ContractAdjustmentType::INDEX) {
                throw ValidationException::withMessages(['index' => 'El ajuste no es de tipo índice.']);
            }
            if (!is_null($adj->value)) {
                throw ValidationException::withMessages(['index' => 'El ajuste ya tiene un valor asignado.']);
            }
            if (!is_null($adj->applied_at)) {
                throw ValidationException::withMessages(['index' => 'El ajuste ya fue aplicado y no puede modificarse.']);
            }

            $indexType = $adj->indexType;
            if (!$indexType) {
                throw ValidationException::withMessages(['index' => 'El ajuste no tiene un tipo de índice válido.']);
            }

            $contract = $adj->contract()->lockForUpdate()->first(); // recomendado
            if (!$contract) {
                throw ValidationException::withMessages(['index' => 'Contrato asociado no encontrado.']);
            }

            // Cálculo según modo
            if ($indexType->calculation_mode === CalculationMode::RATIO) {

                [$percentage, $ratio, $sDate, $fDate, $sVal, $fVal] =
                    $this->calculateRatioWithSnapshot($adj, $contract);

                // Política de tipos: value = %, factor = coef.
                $adj->value             = numToDecimal($percentage, 6); // ej. 6 decimales
                $adj->factor            = numToDecimal($ratio, 6);
                $adj->index_S_date      = $sDate;
                $adj->index_F_date      = $fDate;
                $adj->index_S_value     = numToDecimal($sVal, 6);
                $adj->index_F_value     = numToDecimal($fVal, 6);
            } elseif ($indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {

                $coef = $this->calculateMultiplicativeChain($adj, $contract);
                if ($coef === null) {
                    throw ValidationException::withMessages(['index' => 'No se pudo calcular el coeficiente multiplicativo.']);
                }

                // Si solo tenés coeficiente, podés derivar % si te sirve
                $adj->factor = numToDecimal($coef, 6);
                $adj->value  = numToDecimal(((string)$coef - 1) * 100, 6);

            } else {

                throw ValidationException::withMessages(['index' => 'Modo de cálculo no soportado.']);
            }

            $adj->save();
        });
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
     * Determina la base monetaria al momento de APLICAR (por compatibilidad si no hay snapshot).
     */
    private function resolveBaseAmountForApplication(Contract $contract, ContractAdjustment $adjustment, ?IndexType $indexType): float
    {
        if ($indexType && $indexType->isCumulative()) {
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
     * - Mensual: exige valores EXACTOS para S y F (no permite arrastre hacia atrás).
     * - Diario: usa <= fecha con control de antigüedad (maxIndexAgeDays).
     *
     * @return array [percentage (rounded), ratio, Sdate, Fdate, Sval, Fval]
     */
    private function calculateRatioWithSnapshot(ContractAdjustment $adjustment, Contract $contract): array
    {
        $indexType     = IndexType::find($adjustment->index_type_id);
        $effectiveDate = $adjustment->effective_date->copy();

        // F (fin del tramo)
        $Fdate = $indexType->isMonthlyFrequency()
            ? $effectiveDate->copy()->subMonthNoOverflow()->startOfMonth()
            : $effectiveDate->copy()->subDay();

        // S (inicio del tramo)
        if ($indexType->isCumulative()) {
            $Sdate = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
            $Sdate = $indexType->isMonthlyFrequency() ? $Sdate->startOfMonth() : $Sdate;
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
                    ? $Sdate->subMonthNoOverflow()->startOfMonth()
                    : $Sdate->subDay();
            } else {
                $Sdate = Carbon::parse(($contract->index_base_date ?? $contract->start_date));
                $Sdate = $indexType->isMonthlyFrequency() ? $Sdate->startOfMonth() : $Sdate;
            }
        }

        // --- OBTENCIÓN DE VALORES ---

        if ($indexType->isMonthlyFrequency()) {
            // ✅ Mensual: exigir EXACTO S y F
            $Siv = $this->getMonthlyIndexValueExact($indexType->id, $Sdate);
            $Fiv = $this->getMonthlyIndexValueExact($indexType->id, $Fdate);

            if (!$Siv) {
                throw ValidationException::withMessages([
                    'index' => "Falta el valor de índice para el mes base (S) " . $Sdate->format('Y-m') . ".",
                ]);
            }
            if (!$Fiv) {
                throw ValidationException::withMessages([
                    'index' => "Falta el valor de índice para el mes de aplicación (F) " . $Fdate->format('Y-m') . ".",
                ]);
            }

            $Sval = (float) $Siv->value;
            $Fval = (float) $Fiv->value;

        } else {
            // 📅 Diario: permitir <= pero con control de antigüedad
            $Siv = $this->getIndexValueAtOrBefore($indexType->id, $Sdate);
            $Fiv = $this->getIndexValueAtOrBefore($indexType->id, $Fdate);

            if (!$Siv || !$Fiv) {
                throw ValidationException::withMessages([
                    'index' => 'Faltan valores de índice para S o F.',
                ]);
            }

            // Control de antigüedad del F para diarios
            $daysDiff = Carbon::parse($Fiv->effective_date)->diffInDays($Fdate);
            if ($daysDiff > $this->maxIndexAgeDays) {
                throw ValidationException::withMessages([
                    'index' => "El último valor diario es demasiado antiguo ("
                        . Carbon::parse($Fiv->effective_date)->toDateString()
                        . ") para F " . $Fdate->toDateString() . ".",
                ]);
            }

            $Sval = (float) $Siv->value;
            $Fval = (float) $Fiv->value;
        }

        // --- CÁLCULO ---
        $ratio      = $Fval / $Sval;
        $percentage = ($ratio - 1.0) * 100.0;

        return [
            round($percentage, 4),
            $ratio,
            $Sdate->toDateString(),
            $Fdate->toDateString(),
            $Sval,
            $Fval,
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

    /**
     * Devuelve el IndexValue EXACTO para el mes indicado (día 1 del mes).
     * Si no existe ese mes publicado, retorna null.
     */
    private function getMonthlyIndexValueExact(int $indexTypeId, Carbon $month): ?IndexValue
    {
        $target = $month->copy()->startOfMonth()->toDateString();

        return IndexValue::query()
            ->where('index_type_id', $indexTypeId)
            ->whereDate('effective_date', $target)
            ->first();
    }
}
