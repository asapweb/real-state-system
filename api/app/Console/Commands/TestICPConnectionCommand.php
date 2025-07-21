<?php

namespace App\Console\Commands;

use App\Services\ICLExcelImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestICPConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:test-icp-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conexión y descarga del archivo ICP desde INDEC';

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
        $this->info('🔍 Probando conexión ICP desde INDEC...');

        try {
            // Obtener fuentes disponibles
            $sources = $this->excelImportService->getAvailableSources();

            if (!isset($sources['indec'])) {
                $this->error('❌ Fuente INDEC no encontrada en las fuentes disponibles');
                return 1;
            }

            $indecSource = $sources['indec'];
            $this->info("📡 Fuente: {$indecSource['name']}");
            $this->info("🔗 URL: {$indecSource['url']}");
            $this->info("📝 Descripción: {$indecSource['description']}");

            // Probar conexión HTTP
            $this->info('🌐 Probando conexión HTTP...');

            try {
                $response = Http::timeout(30)->get($indecSource['url']);

                if ($response->successful()) {
                    $this->info('✅ Conexión HTTP exitosa');
                    $this->info("📊 Tamaño del archivo: " . number_format(strlen($response->body())) . " bytes");
                    $this->info("📋 Headers de respuesta:");

                    foreach ($response->headers() as $header => $values) {
                        $this->line("   {$header}: " . implode(', ', $values));
                    }
                } else {
                    $this->warn("⚠️ Conexión HTTP falló con código: {$response->status()}");

                    // Intentar sin verificación SSL
                    $this->info('🔄 Intentando sin verificación SSL...');
                    $response = Http::timeout(30)->withoutVerifying()->get($indecSource['url']);

                    if ($response->successful()) {
                        $this->info('✅ Conexión exitosa sin verificación SSL');
                        $this->info("📊 Tamaño del archivo: " . number_format(strlen($response->body())) . " bytes");
                    } else {
                        $this->error("❌ Conexión falló incluso sin verificación SSL: {$response->status()}");
                        return 1;
                    }
                }

                // Guardar archivo para inspección
                $debugPath = storage_path('app/icp_indec_test.xlsx');
                file_put_contents($debugPath, $response->body());
                $this->info("💾 Archivo guardado para inspección: {$debugPath}");

                // Intentar parsear el archivo
                $this->info('📖 Probando parseo del archivo...');

                $tempFile = tempnam(sys_get_temp_dir(), 'icp_test_');
                file_put_contents($tempFile, $response->body());

                try {
                    $data = $this->excelImportService->parseExcelFile($tempFile, 'indec');

                    if (!empty($data)) {
                        $this->info("✅ Parseo exitoso - Se encontraron " . count($data) . " registros");
                        $this->info("📅 Rango de fechas: " . $data[0]['date'] . " a " . end($data)['date']);
                        $this->info("📊 Muestra de datos:");

                        foreach (array_slice($data, 0, 5) as $index => $row) {
                            $this->line("   {$row['date']}: {$row['value']}");
                        }
                    } else {
                        $this->warn("⚠️ Parseo completado pero no se encontraron datos válidos");
                    }

                    unlink($tempFile);

                } catch (\Exception $e) {
                    $this->error("❌ Error parseando archivo: {$e->getMessage()}");
                    unlink($tempFile);
                    return 1;
                }

            } catch (\Exception $e) {
                $this->error("❌ Error de conexión: {$e->getMessage()}");
                return 1;
            }

            $this->info('✅ Prueba de conexión ICP completada exitosamente');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la prueba: {$e->getMessage()}");
            Log::error('Error en prueba de conexión ICP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
