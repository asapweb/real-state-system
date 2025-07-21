<?php

namespace App\Console\Commands;

use App\Models\IndexType;
use App\Services\ICLExcelImportService;
use Illuminate\Console\Command;

class ImportUVAFromExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:import-uva-excel {--file= : Ruta al archivo Excel local} {--force : Forzar importación incluso si ya existe el valor} {--dry-run : Solo mostrar qué se importaría sin guardar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar valores UVA (Unidad de Valor Adquisitivo) desde archivo Excel oficial del BCRA';

    protected ICLExcelImportService $excelImportService;

    public function __construct(ICLExcelImportService $excelImportService)
    {
        parent::__construct();
        $this->excelImportService = $excelImportService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Iniciando importación UVA desde Excel...');

        try {
            // Obtener el tipo de índice UVA
            $uvaIndexType = IndexType::where('code', 'UVA')->first();

            if (!$uvaIndexType) {
                $this->error('❌ No se encontró el tipo de índice UVA. Asegúrate de que exista en la base de datos.');
                return 1;
            }

            $this->info("📊 Tipo de índice encontrado: {$uvaIndexType->name}");
            $this->info("📡 Fuente: BCRA (Banco Central de la República Argentina)");

            // Configurar parámetros
            $filePath = $this->option('file');
            $force = $this->option('force');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('🔍 Modo DRY RUN - No se guardarán datos');
            }

            if ($filePath) {
                $this->info("📁 Archivo local: {$filePath}");
            }

            // Ejecutar importación
            $result = $this->excelImportService->importFromExcel($uvaIndexType, [
                'file_path' => $filePath,
                'force' => $force,
                'dry_run' => $dryRun,
                'source' => 'bcra_uva' // Especificar fuente para UVA
            ]);

            // Mostrar resultados
            $this->displayResults($result);

            if ($result['success']) {
                $this->info('✅ Importación UVA completada exitosamente');
                return 0;
            } else {
                $this->error('❌ Error en la importación UVA');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error durante la importación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Mostrar resultados de la importación
     */
    private function displayResults(array $result): void
    {
        $this->newLine();
        $this->info('📊 Resultados de la importación:');

        $this->info("✅ Nuevos valores: {$result['new_values']}");
        $this->info("🔄 Valores actualizados: {$result['updated_values']}");
        $this->info("⏭️ Valores omitidos: {$result['skipped_values']}");
        $this->info("❌ Errores: {$result['errors']}");

        if (isset($result['last_import'])) {
            $this->info("📅 Última importación: {$result['last_import']}");
        }

        if (!empty($result['message'])) {
            $this->info("💬 Mensaje: {$result['message']}");
        }
    }
}
