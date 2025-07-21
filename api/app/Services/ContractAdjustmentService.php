<?php

namespace App\Services;

use App\Models\ContractAdjustment;
use App\Models\Contract;
use App\Enums\ContractAdjustmentType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Enums\CalculationMode;

class ContractAdjustmentService
{
    /**
     * Aplica el ajuste al contrato y actualiza los valores correspondientes.
     *
     * @param ContractAdjustment $adjustment
     * @throws InvalidArgumentException
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

        if ($adjustment->applied_at) {
            \Log::warning('⚠️ Ajuste ya fue aplicado', [
                'applied_at' => $adjustment->applied_at,
            ]);
            throw new InvalidArgumentException('El ajuste ya fue aplicado.');
        }
        if (is_null($adjustment->value)) {
            \Log::error('❌ Ajuste no tiene valor para aplicar');
            throw new InvalidArgumentException('El campo value debe estar presente para aplicar el ajuste.');
        }

        // Validar si ya se emitió una cobranza para este período
        $period = normalizePeriodOrFail($adjustment->effective_date);

        $existingVoucher = \App\Models\Voucher::where('status', 'issued')
            ->where('contract_id', $adjustment->contract_id)
            ->where('period', $period)
            ->whereHas('booklet.voucherType', function ($query) {
                $query->where('short_name', 'COB');
            })
            ->first();

        if ($existingVoucher) {
            \Log::warning('⚠️ Ya existe cobranza emitida para el período', [
                'period' => $period,
                'voucher_id' => $existingVoucher->id,
            ]);
            throw new InvalidArgumentException('Ya se emitió una cobranza para este período. No se puede aplicar el ajuste.');
        }

        // Validar que no exista otro ajuste aplicado para el mismo período
        $period = $adjustment->effective_date->format('Y-m');

        $existingAdjustment = ContractAdjustment::where('contract_id', $adjustment->contract_id)
            ->whereNotNull('applied_at')
            ->where('id', '!=', $adjustment->id)
            ->whereRaw("DATE_FORMAT(effective_date, '%Y-%m') = ?", [$period])
            ->first();

        if ($existingAdjustment) {
            \Log::warning('⚠️ Ya existe ajuste aplicado para el período', [
                'period' => $period,
                'existing_adjustment_id' => $existingAdjustment->id,
            ]);
            throw new InvalidArgumentException('Ya existe un ajuste aplicado para este período.');
        }

        $contract = $adjustment->contract;
        if (!$contract) {
            \Log::error('❌ No se encontró el contrato asociado');
            throw new InvalidArgumentException('No se encontró el contrato asociado al ajuste.');
        }

        \Log::info('📋 Información del contrato para aplicación', [
            'contract_id' => $contract->id,
            'monthly_amount' => $contract->monthly_amount,
            'adjustment_type' => $adjustment->type,
            'adjustment_value' => $adjustment->value,
        ]);

        $appliedAmount = null;
        switch ($adjustment->type) {
            case ContractAdjustmentType::INDEX:
                // Para ajustes de índice, verificar el modo de cálculo
                if ($adjustment->indexType && $adjustment->indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {
                    \Log::info('🔗 Aplicando ajuste de índice en modo MULTIPLICATIVE_CHAIN (Casa Propia)', [
                        'monthly_amount' => $contract->monthly_amount,
                        'adjustment_value' => $adjustment->value,
                        'formula' => "monthly_amount * adjustment_value",
                        'note' => 'El valor es un coeficiente multiplicativo directo (ej: 1.15752)',
                    ]);
                    // Para modo multiplicative_chain: aplicar directamente el coeficiente
                    $appliedAmount = round($contract->monthly_amount * $adjustment->value, 2);
                } elseif ($adjustment->indexType && $adjustment->indexType->calculation_mode === CalculationMode::RATIO) {
                    \Log::info('🔄 Aplicando ajuste de índice en modo RATIO (estándar argentino)', [
                        'monthly_amount' => $contract->monthly_amount,
                        'adjustment_value' => $adjustment->value,
                        'formula' => "monthly_amount * (1 + adjustment_value / 100)",
                        'note' => 'El valor ya es un porcentaje calculado de la variación del índice',
                    ]);
                    // Para modo ratio: el valor ya es un porcentaje calculado, aplicar como percentage
                    $appliedAmount = round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
                } else {
                    \Log::info('📈 Aplicando ajuste de índice en modo PERCENTAGE', [
                        'monthly_amount' => $contract->monthly_amount,
                        'adjustment_value' => $adjustment->value,
                        'formula' => "monthly_amount * (1 + adjustment_value / 100)",
                    ]);
                    // Para modo percentage: aplicar monto * (1 + value / 100)
                    $appliedAmount = round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
                }
                break;
            case ContractAdjustmentType::PERCENTAGE:
                \Log::info('📊 Aplicando ajuste porcentual', [
                    'monthly_amount' => $contract->monthly_amount,
                    'percentage' => $adjustment->value,
                    'formula' => "monthly_amount * (1 + percentage / 100)",
                ]);
                $appliedAmount = round($contract->monthly_amount * (1 + $adjustment->value / 100), 2);
                break;
            case ContractAdjustmentType::FIXED:
            case ContractAdjustmentType::NEGOTIATED:
                \Log::info('💰 Aplicando ajuste fijo/negociado', [
                    'monthly_amount' => $contract->monthly_amount,
                    'fixed_amount' => $adjustment->value,
                    'formula' => "fixed_amount (reemplaza monthly_amount)",
                ]);
                $appliedAmount = round($adjustment->value, 2);
                break;
            default:
                \Log::error('❌ Tipo de ajuste no soportado', [
                    'type' => $adjustment->type,
                ]);
                throw new InvalidArgumentException('Tipo de ajuste no soportado: ' . $adjustment->type);
        }

        \Log::info('✅ Cálculo completado', [
            'original_amount' => $contract->monthly_amount,
            'applied_amount' => $appliedAmount,
            'difference' => $appliedAmount - $contract->monthly_amount,
        ]);

        $adjustment->applied_amount = $appliedAmount;
        $adjustment->markAsApplied();

        $contract->save();

        \Log::info('💾 Ajuste aplicado exitosamente', [
            'adjustment_id' => $adjustment->id,
            'applied_at' => $adjustment->applied_at,
            'final_amount' => $appliedAmount,
        ]);
    }

    /**
     * Asigna el valor del índice mensual al ajuste si corresponde.
     *
     * @param ContractAdjustment $adjustment
     * @return bool True si se asignó un valor, false si no se encontró
     */
    public function assignIndexValue(ContractAdjustment $adjustment): bool
    {
        \Log::info('🔍 Iniciando assignIndexValue', [
            'adjustment_id' => $adjustment->id,
            'type' => $adjustment->type,
            'effective_date' => $adjustment->effective_date,
            'index_type_id' => $adjustment->index_type_id,
            'current_value' => $adjustment->value,
            'applied_at' => $adjustment->applied_at,
        ]);

        if (
            $adjustment->type !== ContractAdjustmentType::INDEX ||
            !is_null($adjustment->value) ||
            !is_null($adjustment->applied_at)
        ) {
            \Log::info('❌ Ajuste no cumple condiciones para asignar valor', [
                'is_index' => $adjustment->type === ContractAdjustmentType::INDEX,
                'has_value' => !is_null($adjustment->value),
                'is_applied' => !is_null($adjustment->applied_at),
            ]);
            return false;
        }

        // Obtener el tipo de índice para determinar el modo de cálculo
        $indexType = $adjustment->indexType;
        if (!$indexType) {
            \Log::error('❌ No se encontró el tipo de índice', [
                'index_type_id' => $adjustment->index_type_id,
            ]);
            return false;
        }

        \Log::info('📊 Información del tipo de índice', [
            'index_type_id' => $indexType->id,
            'index_name' => $indexType->name,
            'calculation_mode' => $indexType->calculation_mode,
        ]);

        $contract = $adjustment->contract;
        if (!$contract) {
            \Log::error('❌ No se encontró el contrato asociado', [
                'contract_id' => $adjustment->contract_id,
            ]);
            return false;
        }

        \Log::info('📋 Información del contrato', [
            'contract_id' => $contract->id,
            'start_date' => $contract->start_date,
            'monthly_amount' => $contract->monthly_amount,
        ]);

        if ($indexType->calculation_mode === CalculationMode::RATIO) {
            \Log::info('🔄 Usando modo RATIO para cálculo (estándar argentino)');
            // Para modo ratio: calcular la variación directa del índice entre start_date y effective_date
            $adjustment->value = $this->calculateRatioAdjustment($adjustment, $contract);
        } elseif ($indexType->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN) {
            \Log::info('🔗 Usando modo MULTIPLICATIVE_CHAIN para cálculo');
            // Para modo multiplicative_chain: multiplicar coeficientes mensuales entre sí
            $adjustment->value = $this->calculateMultiplicativeChain($adjustment, $contract);
        } else {
            \Log::info('📈 Usando modo PERCENTAGE para cálculo');

            // Buscar valor de índice por effective_date
            $indexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', $adjustment->effective_date)
                ->whereNotNull('value')
                ->first();

            if (!$indexValue) {
                \Log::warning('⚠️ No se encontró valor de índice para la fecha efectiva', [
                    'effective_date' => $adjustment->effective_date->format('Y-m-d'),
                    'index_type_id' => $adjustment->index_type_id,
                ]);
                return false;
            }

            \Log::info('✅ Valor de índice encontrado por fecha efectiva', [
                'effective_date' => $adjustment->effective_date->format('Y-m-d'),
                'value' => $indexValue->value,
            ]);

            $adjustment->value = $indexValue->value;
        }

        \Log::info('💾 Guardando valor calculado', [
            'final_value' => $adjustment->value,
        ]);

        $adjustment->save();
        return true;
    }

    /**
     * Calcula el ajuste para modo ratio usando la variación directa del índice (estándar argentino)
     * Obtiene el valor del índice en la fecha de inicio del contrato y en la fecha efectiva del ajuste
     * y calcula la variación porcentual entre ambos valores
     *
     * @param ContractAdjustment $adjustment
     * @param \App\Models\Contract $contract
     * @return float|null
     */
    private function calculateRatioAdjustment(ContractAdjustment $adjustment, \App\Models\Contract $contract): ?float
    {
        $startDate = $contract->start_date;
        $effectiveDate = $adjustment->effective_date;
        $indexType = \App\Models\IndexType::find($adjustment->index_type_id);

        \Log::info('🔄 Iniciando cálculo RATIO (estándar argentino)', [
            'start_date' => $startDate,
            'effective_date' => $effectiveDate,
            'index_type_id' => $adjustment->index_type_id,
            'calculation_mode' => $indexType->calculation_mode->value ?? $indexType->calculation_mode,
            'frequency' => $indexType->frequency->value ?? $indexType->frequency,
        ]);

        // Buscar el valor del índice en la fecha de inicio del contrato
        $initialIndexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
            ->where('effective_date', '<=', $startDate)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'desc')
            ->first();

        \Log::info('🔍 Query SQL generada para valor inicial', [
            'sql' => \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', '<=', $startDate)
                ->whereNotNull('value')
                ->orderBy('effective_date', 'desc')
                ->toSql(),
            'bindings' => \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
                ->where('effective_date', '<=', $startDate)
                ->whereNotNull('value')
                ->orderBy('effective_date', 'desc')
                ->getBindings(),
        ]);

        if (!$initialIndexValue) {
            \Log::warning('⚠️ No se encontró valor de índice para la fecha de inicio del contrato', [
                'start_date' => $startDate,
                'index_type_id' => $adjustment->index_type_id,
            ]);
            return null;
        }

        // Buscar el valor del índice en la fecha efectiva del ajuste
        $finalIndexValue = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
            ->where('effective_date', '<=', $effectiveDate)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$finalIndexValue) {
            \Log::warning('⚠️ No se encontró valor de índice para la fecha efectiva del ajuste', [
                'effective_date' => $effectiveDate,
                'index_type_id' => $adjustment->index_type_id,
            ]);
            return null;
        }

        // Obtener los valores
        $initialValue = $initialIndexValue->value;
        $finalValue = $finalIndexValue->value;

        \Log::info('📊 Valores de índice encontrados', [
            'initial_date' => $initialIndexValue->effective_date,
            'initial_value' => $initialValue,
            'final_date' => $finalIndexValue->effective_date,
            'final_value' => $finalValue,
        ]);

        // Calcular la variación porcentual directa
        $percentageChange = $this->calculatePercentageFromRatio($finalValue, $initialValue);

        \Log::info('✅ Cálculo RATIO completado (estándar argentino)', [
            'initial_value' => $initialValue,
            'final_value' => $finalValue,
            'final_percentage' => $percentageChange,
            'formula' => "(({$finalValue} - {$initialValue}) / {$initialValue}) * 100",
        ]);

        return $percentageChange;
    }

    /**
     * Calcula el porcentaje de ajuste basado en valores ratio explícitos
     *
     * @param float $currentValue
     * @param float $baseValue
     * @return float
     */
    private function calculatePercentageFromRatio(float $currentValue, float $baseValue): float
    {
        if ($baseValue == 0) {
            return 0.0;
        }

        $percentageChange = (($currentValue - $baseValue) / $baseValue) * 100;
        return round($percentageChange, 2);
    }

    /**
     * Calculate multiplicative chain adjustment
     * Multiplica coeficientes mensuales entre sí desde el mes siguiente al inicio del contrato hasta el mes del ajuste
     * Metodología oficial Casa Propia: excluye el mes de inicio del contrato, incluye el mes del ajuste
     *
     * @param ContractAdjustment $adjustment
     * @param \App\Models\Contract $contract
     * @return float|null
     */
    private function calculateMultiplicativeChain(ContractAdjustment $adjustment, \App\Models\Contract $contract): ?float
    {
        $startDate = $contract->start_date;
        $effectiveDate = $adjustment->effective_date;
        $indexType = \App\Models\IndexType::find($adjustment->index_type_id);

        \Log::info('🔗 Iniciando cálculo MULTIPLICATIVE_CHAIN (metodología Casa Propia)', [
            'contract_id' => $contract->id,
            'start_date' => $startDate->format('Y-m-d'),
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'index_type_id' => $adjustment->index_type_id,
            'index_type_code' => $indexType->code,
            'calculation_mode' => $indexType->calculation_mode->value ?? $indexType->calculation_mode,
            'frequency' => $indexType->frequency->value ?? $indexType->frequency,
            'methodology' => 'Casa Propia: excluye mes inicio, incluye mes ajuste',
        ]);

        // Generar períodos mensuales (excluye mes inicio, incluye mes ajuste)
        $periods = $this->generateMonthlyPeriods($startDate, $effectiveDate, true);

        if (empty($periods)) {
            \Log::warning('⚠️ No se generaron períodos para el cálculo', [
                'start_date' => $startDate->format('Y-m-d'),
                'effective_date' => $effectiveDate->format('Y-m-d'),
                'note' => 'Esto puede ocurrir si el ajuste es en el mismo mes que el inicio del contrato',
            ]);
            return null;
        }

        \Log::info('📅 Períodos a procesar (metodología Casa Propia)', [
            'start_date' => $startDate->format('Y-m-d'),
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'periods' => $periods,
            'total_periods' => count($periods),
            'excluded_start_month' => $startDate->format('Y-m'),
            'included_end_month' => $effectiveDate->format('Y-m'),
        ]);

        // Buscar todos los valores de índice para las fechas efectivas
        $indexValues = \App\Models\IndexValue::where('index_type_id', $adjustment->index_type_id)
            ->whereIn('effective_date', $periods)
            ->whereNotNull('value')
            ->orderBy('effective_date', 'asc')
            ->get();

        if ($indexValues->isEmpty()) {
            \Log::warning('⚠️ No se encontraron valores de índice para las fechas efectivas', [
                'periods' => $periods,
                'index_type_id' => $adjustment->index_type_id,
                'found_values_count' => $indexValues->count(),
                'methodology' => 'Casa Propia: excluye mes inicio, incluye mes ajuste',
            ]);
            return null;
        }

        // Verificar que tenemos valores para todos los períodos
        $foundPeriods = $indexValues->pluck('effective_date')->map(function($date) {
            return $date->format('Y-m');
        })->toArray();
        $missingPeriods = array_diff($periods, $foundPeriods);

        if (!empty($missingPeriods)) {
            \Log::warning('⚠️ Faltan valores de índice para algunos períodos', [
                'missing_periods' => $missingPeriods,
                'found_periods' => $foundPeriods,
                'total_expected' => count($periods),
                'total_found' => count($foundPeriods),
                'methodology' => 'Casa Propia: excluye mes inicio, incluye mes ajuste',
            ]);
            return null;
        }

        // Extraer los coeficientes y multiplicarlos
        $coefficients = $indexValues->pluck('value')->toArray();

        \Log::info('📊 Coeficientes encontrados (metodología Casa Propia)', [
            'coefficients' => $coefficients,
            'periods' => $foundPeriods,
            'total_coefficients' => count($coefficients),
            'methodology' => 'Casa Propia: excluye mes inicio, incluye mes ajuste',
        ]);

        // Multiplicar todos los coeficientes
        $result = array_reduce($coefficients, function($carry, $coefficient) {
            return $carry * $coefficient;
        }, 1.0);

        \Log::info('✅ Cálculo MULTIPLICATIVE_CHAIN completado (metodología Casa Propia)', [
            'coefficients' => $coefficients,
            'result' => $result,
            'formula' => '1.0 * ' . implode(' * ', $coefficients) . ' = ' . $result,
            'total_coefficients' => count($coefficients),
            'methodology' => 'Casa Propia: excluye mes inicio, incluye mes ajuste',
            'start_date' => $startDate->format('Y-m-d'),
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'excluded_start_month' => $startDate->format('Y-m'),
            'included_end_month' => $effectiveDate->format('Y-m'),
        ]);

        return $result;
    }

    /**
     * Generate monthly periods between two dates
     * For multiplicative_chain: excludes the start month but includes the end month (Casa Propia methodology)
     * For other modes: includes all months between start and end dates
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param bool $excludeStartMonth Whether to exclude the start month (for multiplicative_chain)
     * @return array
     */
    private function generateMonthlyPeriods(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, bool $excludeStartMonth = false): array
    {
        $periods = [];

        if ($excludeStartMonth) {
            // Para multiplicative_chain: comenzar desde el mes siguiente al inicio del contrato
            $currentDate = $startDate->copy()->startOfMonth()->addMonth();

            \Log::info('📅 Generando períodos mensuales (metodología Casa Propia)', [
                'start_date' => $startDate->format('Y-m-d'),
                'effective_date' => $endDate->format('Y-m-d'),
                'start_month_excluded' => $startDate->format('Y-m'),
                'first_month_included' => $currentDate->format('Y-m'),
                'last_month_included' => $endDate->copy()->startOfMonth()->format('Y-m'),
                'note' => 'Excluye el mes de inicio del contrato, incluye el mes del ajuste',
            ]);
        } else {
            // Para otros modos: incluir todos los meses
            $currentDate = $startDate->copy()->startOfMonth();

            \Log::info('📅 Generando períodos mensuales (modo estándar)', [
                'start_date' => $startDate->format('Y-m-d'),
                'effective_date' => $endDate->format('Y-m-d'),
                'first_month_included' => $currentDate->format('Y-m'),
                'last_month_included' => $endDate->copy()->startOfMonth()->format('Y-m'),
                'note' => 'Incluye todos los meses entre las fechas',
            ]);
        }

        $endMonth = $endDate->copy()->startOfMonth();

        while ($currentDate->lte($endMonth)) {
            $periods[] = $currentDate->format('Y-m');
            $currentDate->addMonth();
        }

        \Log::info('📊 Períodos generados', [
            'periods' => $periods,
            'total_periods' => count($periods),
            'methodology' => $excludeStartMonth ? 'Casa Propia: excluye mes inicio, incluye mes ajuste' : 'Estándar: incluye todos los meses',
        ]);

        return $periods;
    }
}
