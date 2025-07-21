<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AssignIndexValuesToAdjustmentsService;

class AdjustmentsAssignIndexValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adjustments:assign-index-values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna valores de índice a los ajustes de contrato pendientes.';

    protected AssignIndexValuesToAdjustmentsService $service;

    public function __construct(AssignIndexValuesToAdjustmentsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $this->info('🚀 Iniciando asignación de valores de índice a ajustes pendientes...');

        \Log::info('🎯 Comando adjustments:assign-index-values iniciado');

        $startTime = microtime(true);
        $total = $this->service->assign();
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        \Log::info('✅ Comando adjustments:assign-index-values completado', [
            'total_updated' => $total,
            'execution_time_seconds' => $executionTime,
        ]);

        $this->info("✅ Se actualizaron {$total} ajustes correctamente en {$executionTime} segundos.");
        return 0;
    }
}
