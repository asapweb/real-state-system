<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class AdminCommandsController extends Controller
{
    /**
     * Ejecutar comando de sincronización de índices
     */
    public function syncIndices(Request $request): JsonResponse
    {
        $request->validate([
            'index_type' => 'required|in:ICL,ICP,UVA',
            'options' => 'array',
            'options.dry_run' => 'boolean',
            'options.force' => 'boolean',
            'options.file' => 'nullable|string',
        ]);

        $indexType = strtolower($request->input('index_type'));
        $options = $request->input('options', []);

        $command = "indices:import-{$indexType}-excel";
        $commandOptions = [];

        if (!empty($options['dry_run'])) {
            $commandOptions[] = '--dry-run';
        }

        if (!empty($options['force'])) {
            $commandOptions[] = '--force';
        }

        if (!empty($options['file'])) {
            $commandOptions[] = "--file={$options['file']}";
        }

        try {
            Log::info('🖥️ Comando ejecutado desde interfaz web', [
                'command' => $command,
                'options' => $commandOptions,
                'user_id' => request()->user()?->id,
            ]);

            $exitCode = Artisan::call($command, $commandOptions);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Log::info('✅ Comando ejecutado exitosamente', [
                    'command' => $command,
                    'output_length' => strlen($output),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Sincronización de {$indexType} completada exitosamente",
                    'output' => $output,
                    'command' => $command,
                ]);
            } else {
                Log::error('❌ Error ejecutando comando', [
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Error ejecutando sincronización de {$indexType}",
                    'output' => $output,
                    'exit_code' => $exitCode,
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción ejecutando comando', [
                'command' => $command,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Error inesperado: {$e->getMessage()}",
                'command' => $command,
            ], 500);
        }
    }

    /**
     * Ejecutar comando de asignación de valores de índice
     */
    public function assignIndexValues(Request $request): JsonResponse
    {
        try {
            Log::info('🖥️ Comando de asignación ejecutado desde interfaz web', [
                'command' => 'adjustments:assign-index-values',
                'user_id' => request()->user()?->id,
            ]);

            $exitCode = Artisan::call('adjustments:assign-index-values');
            $output = Artisan::output();

            if ($exitCode === 0) {
                Log::info('✅ Comando de asignación ejecutado exitosamente', [
                    'output_length' => strlen($output),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Asignación de valores de índice completada exitosamente',
                    'output' => $output,
                    'command' => 'adjustments:assign-index-values',
                ]);
            } else {
                Log::error('❌ Error ejecutando comando de asignación', [
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error ejecutando asignación de valores de índice',
                    'output' => $output,
                    'exit_code' => $exitCode,
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción ejecutando comando de asignación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Error inesperado: {$e->getMessage()}",
                'command' => 'adjustments:assign-index-values',
            ], 500);
        }
    }

    /**
     * Obtener información de comandos disponibles
     */
    public function getAvailableCommands(): JsonResponse
    {
        $commands = [
            'sync_indices' => [
                'name' => 'Sincronización de Índices',
                'description' => 'Importar valores de índices oficiales desde fuentes externas',
                'available_types' => [
                    'ICL' => [
                        'name' => 'Índice de Costo de la Construcción',
                        'source' => 'BCRA',
                        'command' => 'indices:import-icl-excel',
                    ],
                    'ICP' => [
                        'name' => 'Índice de Casa Propia',
                        'source' => 'INDEC',
                        'command' => 'indices:import-icp-excel',
                    ],
                    'UVA' => [
                        'name' => 'Unidad de Valor Adquisitivo',
                        'source' => 'BCRA',
                        'command' => 'indices:import-uva-excel',
                    ],
                ],
            ],
            'assign_index_values' => [
                'name' => 'Asignar Valores de Índice',
                'description' => 'Asignar automáticamente valores de índice a ajustes pendientes',
                'command' => 'adjustments:assign-index-values',
            ],
        ];

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    /**
     * Obtener logs recientes de comandos
     */
    public function getRecentLogs(Request $request): JsonResponse
    {
        $request->validate([
            'lines' => 'integer|min:10|max:1000',
        ]);

        $lines = $request->input('lines', 100);
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo de logs no encontrado',
            ], 404);
        }

        try {
            $logs = [];
            $file = new \SplFileObject($logFile);
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            $startLine = max(0, $totalLines - $lines);
            $file->seek($startLine);

            while (!$file->eof()) {
                $line = $file->current();
                if (trim($line)) {
                    $logs[] = trim($line);
                }
                $file->next();
            }

            // Filtrar solo logs relacionados con comandos
            $filteredLogs = array_filter($logs, function($line) {
                return str_contains($line, 'Comando ejecutado') ||
                       str_contains($line, 'adjustments:assign-index-values') ||
                       str_contains($line, 'indices:import-') ||
                       str_contains($line, '🖥️') ||
                       str_contains($line, '✅') ||
                       str_contains($line, '❌');
            });

            return response()->json([
                'success' => true,
                'logs' => array_values($filteredLogs),
                'total_lines' => count($filteredLogs),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error leyendo logs: {$e->getMessage()}",
            ], 500);
        }
    }
}
