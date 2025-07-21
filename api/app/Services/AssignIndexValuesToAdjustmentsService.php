<?php

namespace App\Services;

use App\Models\ContractAdjustment;
use App\Enums\ContractAdjustmentType;
use Illuminate\Support\Carbon;

class AssignIndexValuesToAdjustmentsService
{
    protected ContractAdjustmentService $contractAdjustmentService;

    public function __construct(ContractAdjustmentService $contractAdjustmentService)
    {
        $this->contractAdjustmentService = $contractAdjustmentService;
    }

    /**
     * Asigna valores de índice a todos los ajustes pendientes y retorna la cantidad actualizada.
     *
     * @return int
     */
    public function assign(): int
    {
        $today = now()->format('Y-m-d');

        \Log::info('🔍 Iniciando asignación de valores de índice', [
            'today' => $today,
        ]);

        $adjustments = ContractAdjustment::where('type', ContractAdjustmentType::INDEX)
            ->whereNull('value')
            ->whereDate('effective_date', '<=', $today)
            ->whereNull('applied_at')
            ->get();

        \Log::info('📊 Ajustes encontrados para procesar', [
            'total_found' => $adjustments->count(),
            'adjustments' => $adjustments->map(function($adj) {
                return [
                    'id' => $adj->id,
                    'contract_id' => $adj->contract_id,
                    'effective_date' => $adj->effective_date,
                    'index_type_id' => $adj->index_type_id,
                ];
            })->toArray(),
        ]);

        $count = 0;
        $processed = [];
        $failed = [];

        foreach ($adjustments as $adjustment) {
            \Log::info('🔄 Procesando ajuste', [
                'adjustment_id' => $adjustment->id,
                'contract_id' => $adjustment->contract_id,
                'effective_date' => $adjustment->effective_date,
                'index_type_id' => $adjustment->index_type_id,
            ]);

            if ($this->contractAdjustmentService->assignIndexValue($adjustment)) {
                $count++;
                $processed[] = $adjustment->id;
                \Log::info('✅ Ajuste procesado exitosamente', [
                    'adjustment_id' => $adjustment->id,
                    'new_value' => $adjustment->value,
                ]);
            } else {
                $failed[] = $adjustment->id;
                \Log::warning('⚠️ Ajuste no pudo ser procesado', [
                    'adjustment_id' => $adjustment->id,
                ]);
            }
        }

        \Log::info('📈 Resumen de procesamiento', [
            'total_found' => $adjustments->count(),
            'successful' => $count,
            'failed' => count($failed),
            'processed_ids' => $processed,
            'failed_ids' => $failed,
        ]);

        return $count;
    }
}
