<?php

namespace App\Services;

use App\Models\IndexType;
use App\Models\IndexValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ICLExcelImportService
{
    /**
     * URLs de los archivos Excel oficiales
     */
    private const EXCEL_URLS = [
        'bcra' => 'https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_icl.xls',
        'indec' => 'https://www.indec.gob.ar/ftp/cuadros/economia/icp_mensual.xls',
        'bcra_uva' => 'https://www.bcra.gob.ar/Pdfs/PublicacionesEstadisticas/diar_uva.xls',
    ];

    /**
     * Importar datos desde archivo Excel
     * @param IndexType $indexType
     * @param array $options ['file_path' => null, 'force' => false, 'source' => null]
     */
    public function importFromExcel(IndexType $indexType, array $options = []): array
    {
        // Determinar la fuente basada en el tipo de índice o la opción proporcionada
        $source = $options['source'] ?? $this->getSourceForIndexType($indexType);
        $filePath = $options['file_path'] ?? null;
        $force = $options['force'] ?? false;
        $dryRun = $options['dry_run'] ?? false;

        $result = [
            'success' => false,
            'new_values' => 0,
            'updated_values' => 0,
            'skipped_values' => 0,
            'errors' => 0,
            'last_import' => null,
            'message' => '',
            'source_used' => $source,
        ];

        try {
            // Si no se proporciona un archivo, intentar descargarlo
            if (!$filePath) {
                $filePath = $this->downloadExcelFile($source);
                if (!$filePath) {
                    $result['message'] = "No se pudo descargar el archivo Excel desde {$source}";
                    return $result;
                }
            }

            // Procesar el archivo Excel
            $excelData = $this->parseExcelFile($filePath, $source);
            if (empty($excelData)) {
                $result['message'] = "No se pudieron extraer datos del archivo Excel";
                return $result;
            }

            Log::info('Datos extraídos del Excel', [
                'count' => count($excelData),
                'sample' => array_slice($excelData, 0, 3),
                'source' => $source,
                'index_type' => $indexType->code
            ]);

            // Obtener la última fecha existente en la base de datos para este index_type
            $latestDateInDb = IndexValue::where('index_type_id', $indexType->id)
                ->orderByDesc('effective_date')
                ->value('effective_date');

            Log::info('Última fecha en base de datos', [
                'latest_date' => $latestDateInDb,
                'force_mode' => $force,
                'index_type' => $indexType->code
            ]);

            // Filtrar datos si no se está usando force
            if (!$force && $latestDateInDb) {
                $originalCount = count($excelData);
                $excelData = array_filter($excelData, function ($entry) use ($latestDateInDb) {
                    return $entry['date'] > $latestDateInDb;
                });

                $filteredCount = count($excelData);
                $skippedCount = $originalCount - $filteredCount;

                Log::info('Filtrado de datos completado', [
                    'original_count' => $originalCount,
                    'filtered_count' => $filteredCount,
                    'skipped_count' => $skippedCount,
                    'latest_date_in_db' => $latestDateInDb,
                    'index_type' => $indexType->code
                ]);

                $result['skipped_values'] = $skippedCount;
            }

            // Procesar cada valor
            foreach ($excelData as $data) {
                $date = $data['date'] ?? null;
                $value = $data['value'] ?? null;

                if (!$date || !is_numeric($value)) {
                    $result['errors']++;
                    continue;
                }

                $existing = IndexValue::where('index_type_id', $indexType->id)
                    ->where('effective_date', $date)
                    ->first();

                if ($existing && !$force) {
                    $result['skipped_values']++;
                    continue;
                }

                if (!$dryRun) {
                    if ($existing) {
                        $existing->update(['value' => $value]);
                        $result['updated_values']++;
                    } else {
                        IndexValue::create([
                            'index_type_id' => $indexType->id,
                            'effective_date' => $date,
                            'value' => $value,
                            'calculation_mode' => $indexType->calculation_mode
                        ]);
                        $result['new_values']++;
                    }
                } else {
                    $existing ? $result['updated_values']++ : $result['new_values']++;
                }
            }

            // Limpiar archivo temporal si fue descargado
            if (!$options['file_path'] && file_exists($filePath)) {
                unlink($filePath);
            }

            $result['success'] = true;
            $result['last_import'] = now()->format('Y-m-d H:i:s');
            $result['message'] = "Importación completada exitosamente desde {$source}";

        } catch (\Exception $e) {
            Log::error('Error en importación desde Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'source' => $source,
                'index_type' => $indexType->code
            ]);
            $result['message'] = "Error: {$e->getMessage()}";
        }

        return $result;
    }

    /**
     * Determinar la fuente apropiada para el tipo de índice
     */
    private function getSourceForIndexType(IndexType $indexType): string
    {
        return match ($indexType->code) {
            'ICL' => 'bcra',
            'UVA' => 'bcra_uva',
            default => 'bcra'
        };
    }

    /**
     * Descargar archivo Excel desde la fuente especificada
     */
    private function downloadExcelFile(string $source): ?string
    {
        try {
            $url = self::EXCEL_URLS[$source] ?? null;
            if (!$url) {
                throw new \InvalidArgumentException("Fuente no soportada: {$source}");
            }

            // Intentar descarga segura primero
            Log::info('Intentando descarga segura', ['url' => $url]);

            try {
                $response = Http::timeout(60)->get($url);

                if ($response->successful()) {
                    Log::info('Descarga exitosa con verificación SSL');
                } else {
                    throw new \Exception('HTTP error: ' . $response->status());
                }

            } catch (\Exception $e) {
                Log::warning('Descarga segura falló, intentando sin verificación SSL', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);

                // Fallback: intentar sin verificación SSL
                $response = Http::timeout(60)
                    ->withoutVerifying()
                    ->get($url);

                if (!$response->successful()) {
                    Log::error('Error descargando archivo Excel', [
                        'url' => $url,
                        'status' => $response->status()
                    ]);
                    return null;
                }

                Log::info('Descarga exitosa sin verificación SSL');
            }

            // Guardar archivo temporal
            $tempFile = tempnam(sys_get_temp_dir(), 'icl_excel_');
            file_put_contents($tempFile, $response->body());

            // Guardar copia para inspección manual
            if ($source === 'bcra') {
                $debugPath = storage_path('app/icl_bcra_download.xlsx');
                file_put_contents($debugPath, $response->body());
                Log::info('Archivo BCRA guardado para inspección manual', ['path' => $debugPath]);
            }

            return $tempFile;

        } catch (\Exception $e) {
            Log::error('Error descargando archivo Excel', [
                'error' => $e->getMessage(),
                'source' => $source
            ]);
            return null;
        }
    }

    /**
     * Parsear archivo Excel y extraer datos ICL
     */
    private function parseExcelFile(string $filePath, string $source): array
    {
        try {
            Log::info('Iniciando parsing del archivo Excel', [
                'file_path' => $filePath,
                'source' => $source,
                'file_exists' => file_exists($filePath),
                'file_size' => filesize($filePath)
            ]);

            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            Log::info('Archivo Excel cargado correctamente', [
                'sheet_name' => $worksheet->getTitle(),
                'highest_row' => $worksheet->getHighestRow(),
                'highest_column' => $worksheet->getHighestColumn()
            ]);

            // Usar parser específico según la fuente
            if ($source === 'bcra_uva') {
                $data = $this->parseUVAFormat($worksheet);
            } else {
                $data = $this->parseINDECFormat($worksheet);
            }

            Log::info('Parsing completado', [
                'data_count' => count($data),
                'sample_data' => array_slice($data, 0, 3)
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('Error parseando archivo Excel', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'source' => $source,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Parsear formato UVA del BCRA
     */
    private function parseUVAFormat($worksheet): array
    {
        $data = [];
        $highestRow = $worksheet->getHighestRow();

        Log::info('Iniciando parsing formato UVA BCRA', [
            'highest_row' => $highestRow
        ]);

        // Para UVA, buscar encabezados en un rango más amplio (primeras 20 filas)
        $dateColumn = null;
        $valueColumn = null;
        $headerRow = null;

        for ($headerRowIndex = 1; $headerRowIndex <= 20; $headerRowIndex++) {
            Log::info('Buscando encabezados UVA en fila', ['row' => $headerRowIndex]);

            for ($col = 1; $col <= 10; $col++) {
                $header = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $headerRowIndex)->getValue();

                if ($headerRowIndex <= 5) { // Log solo las primeras 5 filas para debug
                    Log::info('Revisando celda UVA', [
                        'row' => $headerRowIndex,
                        'column' => $col,
                        'coordinate' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $headerRowIndex,
                        'header_value' => $header
                    ]);
                }

                // Buscar encabezados específicos de UVA
                if (stripos($header, 'date') !== false || stripos($header, 'fecha') !== false || stripos($header, 'period') !== false) {
                    $dateColumn = $col;
                    $headerRow = $headerRowIndex;
                    Log::info('Columna de fecha UVA encontrada', ['column' => $col, 'header' => $header, 'row' => $headerRowIndex]);
                }
                if (stripos($header, 'uva') !== false || stripos($header, 'value') !== false || stripos($header, 'valor') !== false || stripos($header, 'coefficient') !== false) {
                    $valueColumn = $col;
                    $headerRow = $headerRowIndex;
                    Log::info('Columna de valor UVA encontrada', ['column' => $col, 'header' => $header, 'row' => $headerRowIndex]);
                }
            }

            // Si encontramos ambas columnas, salir del bucle
            if ($dateColumn && $valueColumn) {
                break;
            }
        }

        Log::info('Resultado búsqueda encabezados UVA', [
            'date_column' => $dateColumn,
            'value_column' => $valueColumn,
            'header_row' => $headerRow
        ]);

        if (!$dateColumn || !$valueColumn) {
            Log::error('No se encontraron las columnas requeridas para UVA');
            throw new \Exception('No se encontraron las columnas de fecha y valor en el formato UVA BCRA');
        }

        // Procesar filas de datos (empezar desde la fila siguiente a los encabezados)
        $processedRows = 0;
        $startRow = $headerRow + 1;

        Log::info('Iniciando procesamiento de datos UVA', [
            'start_row' => $startRow,
            'end_row' => $highestRow
        ]);

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $dateValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateColumn) . $row)->getValue();
            $valueValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($valueColumn) . $row)->getValue();

            if ($row <= $startRow + 4) { // Log solo las primeras 5 filas de datos para debug
                Log::info('Procesando fila de datos UVA', [
                    'row' => $row,
                    'date_value' => $dateValue,
                    'value_value' => $valueValue,
                    'is_numeric' => is_numeric($valueValue)
                ]);
            }

            if ($dateValue && is_numeric($valueValue)) {
                $date = $this->parseDate($dateValue);
                if ($date) {
                    $data[] = [
                        'date' => $date,
                        'value' => (float) $valueValue
                    ];
                    $processedRows++;
                }
            }
        }

        Log::info('Procesamiento UVA completado', [
            'total_rows' => $highestRow - $startRow + 1,
            'processed_rows' => $processedRows,
            'data_count' => count($data)
        ]);

        return $data;
    }

    /**
     * Parsear formato INDEC
     */
    private function parseINDECFormat($worksheet): array
    {
        $data = [];
        $highestRow = $worksheet->getHighestRow();

        Log::info('Iniciando parsing formato INDEC', [
            'highest_row' => $highestRow
        ]);

        // Buscar encabezados en las primeras 10 filas
        $dateColumn = null;
        $valueColumn = null;
        $headerRow = null;

        for ($headerRowIndex = 1; $headerRowIndex <= 10; $headerRowIndex++) {
            Log::info('Buscando encabezados en fila', ['row' => $headerRowIndex]);

            for ($col = 1; $col <= 10; $col++) {
                $header = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $headerRowIndex)->getValue();

                if ($headerRowIndex <= 3) { // Log solo las primeras 3 filas para debug
                    Log::info('Revisando celda', [
                        'row' => $headerRowIndex,
                        'column' => $col,
                        'coordinate' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $headerRowIndex,
                        'header_value' => $header
                    ]);
                }

                if (stripos($header, 'fecha') !== false || stripos($header, 'date') !== false) {
                    $dateColumn = $col;
                    $headerRow = $headerRowIndex;
                    Log::info('Columna de fecha encontrada', ['column' => $col, 'header' => $header, 'row' => $headerRowIndex]);
                }
                if (stripos($header, 'valor') !== false || stripos($header, 'icl') !== false || stripos($header, 'value') !== false) {
                    $valueColumn = $col;
                    $headerRow = $headerRowIndex;
                    Log::info('Columna de valor encontrada', ['column' => $col, 'header' => $header, 'row' => $headerRowIndex]);
                }
            }

            // Si encontramos ambas columnas, salir del bucle
            if ($dateColumn && $valueColumn) {
                break;
            }
        }

        Log::info('Resultado búsqueda encabezados', [
            'date_column' => $dateColumn,
            'value_column' => $valueColumn,
            'header_row' => $headerRow
        ]);

        if (!$dateColumn || !$valueColumn) {
            Log::error('No se encontraron las columnas requeridas');
            throw new \Exception('No se encontraron las columnas de fecha y valor en el formato INDEC');
        }

        // Procesar filas de datos (empezar desde la fila siguiente a los encabezados)
        $processedRows = 0;
        $startRow = $headerRow + 1;

        Log::info('Iniciando procesamiento de datos', [
            'start_row' => $startRow,
            'end_row' => $highestRow
        ]);

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $dateValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateColumn) . $row)->getValue();
            $valueValue = $worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($valueColumn) . $row)->getValue();

            if ($row <= $startRow + 4) { // Log solo las primeras 5 filas de datos para debug
                Log::info('Procesando fila de datos', [
                    'row' => $row,
                    'date_value' => $dateValue,
                    'value_value' => $valueValue,
                    'is_numeric' => is_numeric($valueValue)
                ]);
            }

            if ($dateValue && is_numeric($valueValue)) {
                $date = $this->parseDate($dateValue);
                if ($date) {
                    $data[] = [
                        'date' => $date,
                        'value' => (float) $valueValue
                    ];
                    $processedRows++;
                }
            }
        }

        Log::info('Procesamiento completado', [
            'total_rows' => $highestRow - $startRow + 1,
            'processed_rows' => $processedRows,
            'data_count' => count($data)
        ]);

        return $data;
    }

    /**
     * Parsear fecha desde diferentes formatos
     */
    private function parseDate($dateValue): ?string
    {
        if (!$dateValue) {
            return null;
        }

        // Si ya es una fecha de Carbon
        if ($dateValue instanceof Carbon) {
            return $dateValue->format('Y    -m-d');
        }

        // Si es un string, intentar parsearlo
        if (is_string($dateValue)) {
            // Intentar diferentes formatos
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'Y-m-d H:i:s',
                'd/m/Y H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateValue);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Si es un número (timestamp de Excel)
        if (is_numeric($dateValue)) {
            try {
                $date = Carbon::createFromTimestamp(($dateValue - 25569) * 86400);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Obtener URLs disponibles
     */
    public function getAvailableSources(): array
    {
        return [
            'bcra' => [
                'name' => 'BCRA - Banco Central de la República Argentina',
                'url' => self::EXCEL_URLS['bcra'],
                'description' => 'Archivo Excel oficial del BCRA - Índice de Contratos de Locación'
            ],
            'indec' => [
                'name' => 'INDEC - Instituto Nacional de Estadística y Censos',
                'url' => self::EXCEL_URLS['indec'],
                'description' => 'Archivo Excel oficial del INDEC - Índice de Contratos de Locación'
            ],
            'bcra_uva' => [
                'name' => 'BCRA - Banco Central de la República Argentina',
                'url' => self::EXCEL_URLS['bcra_uva'],
                'description' => 'Archivo Excel oficial del BCRA - Índice de Contratos de Locación'
            ]
        ];
    }
}
