<?php

namespace App\Services;

use App\Models\ContractAdjustment;
use App\Models\Contract;
use App\Enums\ContractAdjustmentType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Enums\CalculationMode;
use Illuminate\Validation\ValidationException;
class ContractAdjustmentService
{
    private const MAX_INDEX_AGE_DAYS = 15;
    private int $maxIndexAgeDays; // tolerancia de días para índices diarios

    public function __construct()
    {
        // Lee desde .env y usa 15 como valor por defecto
        $this->maxIndexAgeDays = (int) config('contracts.index_max_age_days', env('INDEX_MAX_AGE_DAYS', 0));
    }

    /**
     * Aplica el ajuste al contrato y actualiza los valores correspondientes.
     */
    public function apply(ContractAdjustment $adjustment): void
    {
        \Log::info('🚀 Iniciando aplicación de ajuste', [
            'adjustment_id' => $adjustment->id,
            'type' => $adjustment->type,
            'value' => $adjustment->value,
            'effective_date' => $adjustment->effective_date,
            'contract_id' => $adjustment->contract_id,
        ]);

        // 🔒 Validaciones previas
        if ($adjustment->applied_at) {
            \Log::warning('⚠️ Ajuste ya aplicado', ['applied_at' => $adjustment->applied_at]);
            throw ValidationException::withMessages([
                'adjustment' => 'El ajuste ya fue aplicado.',
            ]);
        }

        if (is_null($adjustment->value)) {
            \Log::error('❌ Ajuste sin valor asignado');
            throw ValidationException::withMessages([
                'adjustment' => 'El ajuste no tiene un valor asignado. Debe asignarse antes de aplicar.',
            ]);
        }

        $period = normalizePeriodOrFail($adjustment->effective_date);
        \Log::info('📅 Período normalizado para validación de cobranzas', ['period' => $period]);

        // Validar que no haya cobranzas emitidas para ese período
        $existingVoucher = \App\Models\Voucher::where('status', 'issued')
            ->where('contract_id', $adjustment->contract_id)
            ->where('period', $period)
            ->whereHas('booklet.voucherType', fn($q) => $q->where('short_name', 'COB'))
            ->first();

        if ($existingVoucher) {
            \Log::warning('⚠️ Cobranza existente detectada', ['voucher_id' => $existingVoucher->id]);
            throw ValidationException::withMessages([
                'adjustment' => 'Ya se emitió una cobranza para este período. No se puede aplicar el ajuste.',
            ]);
        }

        // Validar que no haya otro ajuste aplicado en el mismo período
        $periodStr = $adjustment->effective_date->format('Y-m');
        $existingAdjustment = ContractAdjustment::where('contract_id', $adjustment->contract_id)
            ->whereNotNull('applied_at')
            ->where('id', '!=', $adjustment->id)
            ->whereRaw("DATE_FORMAT(effective_date, '%Y-%m') = ?", [$periodStr])
            ->first();

        if ($existingAdjustment) {
            \Log::warning('⚠️ Ajuste duplicado en período', [
                'existing_adjustment_id' => $existingAdjustment->id,
                'period' => $periodStr,
            ]);
            throw ValidationException::withMessages([
                'adjustment' => 'Ya existe un ajuste aplicado para este período.',
            ]);
        }

        $contract = $adjustment->contract;
        if (!$contract) {
            \Log::error('❌ Contrato no encontrado');
            throw ValidationException::withMessages([
                'adjustment' => 'No se encontró el contrato asociado al ajuste.',
            ]);
        }

        \Log::info('📋 Datos del contrato', [
            'contract_id' => $contract->id,
            'monthly_amount' => $contract->monthly_amount,
        ]);

        // 🔥 Calcular monto ajustado
        $appliedAmount = match ($adjustment->type) {
            ContractAdjustmentType::INDEX => $this->applyIndexAdjustment($contract, $adjustment),
            ContractAdjustmentType::PERCENTAGE => round($contract->monthly_amount * (1 + $adjustment->value / 100), 2),
            ContractAdjustmentType::FIXED, ContractAdjustmentType::NEGOTIATED => round($adjustment->value, 2),
            default => throw ValidationException::withMessages([
                'adjustment' => 'Tipo de ajuste no soportado: ' . $adjustment->type,
            ]),
        };

        \Log::info('✅ Ajuste calculado', [
            'original_amount' => $contract->monthly_amount,
            'applied_amount' => $appliedAmount,
            'difference' => $appliedAmount - $contract->monthly_amount,
        ]);

        // 💾 Aplicar
        $adjustment->applied_amount = $appliedAmount;
        $adjustment->markAsApplied();

        \Log::info('💾 Ajuste aplicado correctamente', [
            'adjustment_id' => $adjustment->id,
            'applied_at' => $adjustment->applied_at,
            'final_amount' => $appliedAmount,
        ]);
    }


    private function applyIndexAdjustment(Contract $contract, ContractAdjustment $adjustment): float
    {
        if ($adjustment->indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {
            \Log::info('🔗 Aplicando ajuste multiplicativo', [
                'coeficiente' => $adjustment->value
            ]);
            return round($contract->monthly_amount * $adjustment->value, 2);
        }
        \Log::info('📈 Aplicando ajuste porcentual por índice', [
            'percentage' => $adjustment->value
        ]);
        return round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
    }

    public function assignIndexValue(ContractAdjustment $adjustment): void
    {
        \Log::info('🔍 Iniciando assignIndexValue', [
            'adjustment_id' => $adjustment->id,
            'type' => $adjustment->type,
            'effective_date' => $adjustment->effective_date,
            'index_type_id' => $adjustment->index_type_id,
            'current_value' => $adjustment->value,
            'applied_at' => $adjustment->applied_at,
        ]);

        // 🔒 Validaciones previas
        if ($adjustment->type !== ContractAdjustmentType::INDEX) {
            throw ValidationException::withMessages([
                'index' => 'El ajuste no es de tipo índice.',
            ]);
        }

        if (!is_null($adjustment->value)) {
            throw ValidationException::withMessages([
                'index' => 'El ajuste ya tiene un valor asignado.',
            ]);
        }

        if (!is_null($adjustment->applied_at)) {
            throw ValidationException::withMessages([
                'index' => 'El ajuste ya fue aplicado y no puede modificarse.',
            ]);
        }

        $indexType = $adjustment->indexType;
        if (!$indexType) {
            throw ValidationException::withMessages([
                'index' => 'El ajuste no tiene un tipo de índice válido.',
            ]);
        }

        $contract = $adjustment->contract;
        if (!$contract) {
            throw ValidationException::withMessages([
                'index' => 'El ajuste no está vinculado a un contrato válido.',
            ]);
        }

        // 🔥 Cálculo según modo del índice
        if ($indexType->calculation_mode === CalculationMode::RATIO) {
            $percentageChange = $this->calculateRatioAdjustment($adjustment, $contract);
            if (is_null($percentageChange)) {
                \Log::warning('⚠️ No se pudo calcular variación para índice tipo ratio', [
                    'adjustment_id' => $adjustment->id,
                ]);
                throw ValidationException::withMessages([
                    'index' => 'No se pudo calcular la variación del índice (ratio). Verifique que existan valores para las fechas requeridas.',
                ]);
            }
            $adjustment->value = $percentageChange;
            \Log::info('✅ Índice tipo ratio asignado con porcentaje', [
                'percentage' => $percentageChange,
            ]);
        } elseif ($indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {
            $coef = $this->calculateMultiplicativeChain($adjustment, $contract);
            if (is_null($coef)) {
                \Log::warning('⚠️ No se pudo calcular coeficiente multiplicativo', [
                    'adjustment_id' => $adjustment->id,
                ]);
                throw ValidationException::withMessages([
                    'index' => 'No se pudo calcular el coeficiente multiplicativo. Verifique que existan valores de índice para todos los períodos requeridos.',
                ]);
            }
            $adjustment->value = $coef;
            \Log::info('✅ Índice tipo multiplicative_chain asignado', [
                'coefficient' => $coef,
            ]);
        } else {
            throw ValidationException::withMessages([
                'index' => 'El tipo de cálculo del índice no está soportado.',
            ]);
        }

        // 💾 Guardar valor calculado
        $adjustment->save();
    }




    private function calculateRatioAdjustment(ContractAdjustment $adjustment, Contract $contract): ?float
    {
        $startDate = $contract->start_date;
        $effectiveDate = $adjustment->effective_date;
        $indexType = \App\Models\IndexType::find($adjustment->index_type_id);

        \Log::info('🔄 Cálculo RATIO', [
            'start_date' => $startDate,
            'effective_date' => $effectiveDate,
            'frequency' => $indexType->frequency->value ?? $indexType->frequency,
        ]);

        // Valor inicial (sin cambios)
        $initialIndexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
            ->where('effective_date', '<=', $startDate)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'desc')
            ->first();
        if (!$initialIndexValue) {
            \Log::warning('⚠️ Valor inicial no encontrado');
            return null;
        }

        // Valor final según frecuencia
        if ($indexType->frequency === 'monthly') {
            $expectedMonth = $effectiveDate->format('Y-m');
            $finalIndexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
                ->whereRaw("DATE_FORMAT(effective_date, '%Y-%m') = ?", [$expectedMonth])
                ->whereNotNull('value')
                ->first();

            if (!$finalIndexValue) {
                \Log::warning('⚠️ No hay valor mensual para el mes del ajuste', ['expected_month' => $expectedMonth]);
                return null;
            }
        } else { // Diario
            $finalIndexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', '<=', $effectiveDate)
                ->whereNotNull('value')
                ->orderBy('effective_date', 'desc')
                ->first();

            if (!$finalIndexValue) {
                \Log::warning('⚠️ No hay valor diario disponible antes de la fecha efectiva');
                return null;
            }

            // Validar antigüedad máxima para índices diarios (ej. $this->maxIndexAgeDays días)
            $daysDiff = $finalIndexValue->effective_date->diffInDays($effectiveDate);
            if ($daysDiff > $this->maxIndexAgeDays) {
                \Log::warning('⚠️ Valor de índice demasiado antiguo para cálculo diario', [
                    'final_index_date' => $finalIndexValue->effective_date,
                    'effective_date' => $effectiveDate,
                    'days_diff' => $daysDiff,
                ]);
                return null;
            }
        }

        // Cálculo
        $percentageChange = $this->calculatePercentageFromRatio($finalIndexValue->value, $initialIndexValue->value);
        \Log::info('✅ RATIO calculado', [
            'initial_value' => $initialIndexValue->value,
            'final_value' => $finalIndexValue->value,
            'percentage' => $percentageChange,
        ]);

        return $percentageChange;
    }


    private function calculatePercentageFromRatio(float $currentValue, float $baseValue): float
    {
        return $baseValue == 0 ? 0.0 : round((($currentValue - $baseValue) / $baseValue) * 100, 2);
    }

    private function calculateMultiplicativeChain(ContractAdjustment $adjustment, Contract $contract): ?float
    {
        $startDate = $contract->start_date;
        $effectiveDate = $adjustment->effective_date;
        $indexType = \App\Models\IndexType::find($adjustment->index_type_id);

        \Log::info('🔗 Cálculo MULTIPLICATIVE_CHAIN', [
            'contract_id' => $contract->id,
            'start_date' => $startDate,
            'effective_date' => $effectiveDate,
            'index_type_id' => $adjustment->index_type_id,
            'frequency' => $indexType->frequency->value ?? $indexType->frequency,
        ]);

        // Generar períodos según frecuencia
        $periods = $indexType->frequency === 'monthly'
            ? $this->generateMonthlyPeriods($startDate, $effectiveDate, true)
            : $this->generateDailyPeriods($startDate, $effectiveDate);

        if (empty($periods)) {
            \Log::warning('⚠️ No hay períodos generados', [
                'start_date' => $startDate,
                'effective_date' => $effectiveDate,
            ]);
            return null;
        }

        // Buscar valores de índice
        $indexValues = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
            ->whereIn('effective_date', $periods)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'asc')
            ->get();

        if ($indexValues->isEmpty()) {
            \Log::warning('⚠️ No se encontraron valores de índice', ['expected_periods' => $periods]);
            return null;
        }

        // Validar valores faltantes
        $foundPeriods = $indexValues->pluck('effective_date')->map(
            fn($d) => $d->format($indexType->frequency === 'monthly' ? 'Y-m-d' : 'Y-m-d')
        )->toArray();

        $missingPeriods = array_diff($periods, $foundPeriods);
        throw \Illuminate\Validation\ValidationException::withMessages([
            'periods' => $periods,
            'found_periods' => $foundPeriods,
            'missing_periods' => $missingPeriods,
        ]);
        if (!empty($missingPeriods)) {
            \Log::warning('⚠️ Faltan valores de índice en la cadena', [
                'missing_periods' => $missingPeriods,
                'found_periods' => $foundPeriods,
            ]);
            return null;
        }

        // Validación de antigüedad para índices diarios
        if ($indexType->frequency === 'daily') {
            $lastIndexDate = $indexValues->last()->effective_date;
            $daysDiff = $lastIndexDate->diffInDays($effectiveDate);

            if ($daysDiff > $this->maxIndexAgeDays) { // tolerancia configurable
                \Log::warning('⚠️ Valor diario demasiado antiguo para ajuste', [
                    'last_index_date' => $lastIndexDate->format('Y-m-d'),
                    'effective_date' => $effectiveDate->format('Y-m-d'),
                    'days_diff' => $daysDiff,
                ]);
                return null;
            }
        }

        // Multiplicar coeficientes
        $coefficients = $indexValues->pluck('value')->toArray();
        $result = array_reduce($coefficients, fn($carry, $coef) => $carry * $coef, 1.0);

        \Log::info('✅ MULTIPLICATIVE_CHAIN calculado', [
            'coefficients' => $coefficients,
            'result' => $result,
            'formula' => implode(' * ', $coefficients),
        ]);

        return $result;
    }


    private function generateMonthlyPeriods(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, bool $excludeStartMonth = false): array
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'excludeStartMonth' => $excludeStartMonth,
        ]);
        $periods = [];
        $currentDate = $excludeStartMonth
            ? $startDate->copy()->startOfMonth()->addMonth()
            : $startDate->copy()->startOfMonth();
        $endMonth = $endDate->copy()->startOfMonth();

        while ($currentDate->lte($endMonth)) {
            $periods[] = $currentDate->format('Y-m');
            $currentDate->addMonth();
        }
        return $periods;
    }

    private function generateDailyPeriods(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $periods = [];
        $currentDate = $startDate->copy()->addDay();
        while ($currentDate->lte($endDate)) {
            $periods[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        return $periods;
    }
}
