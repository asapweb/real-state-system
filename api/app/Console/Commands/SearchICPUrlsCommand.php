<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchICPUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:search-icp-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buscar URLs alternativas para el archivo ICP del INDEC';

    /**
     * URLs alternativas para probar
     */
    private const ICP_URLS = [
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_mensual.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_anual.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_trimestral.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_serie.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_historico.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_actualizado.xls',
        'https://www.indec.gob.ar/ftp/cuadros/economia/icp_latest.xls',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Buscando URLs alternativas para ICP...');
        $this->newLine();

        $workingUrls = [];

        foreach (self::ICP_URLS as $url) {
            $this->info("🌐 Probando: {$url}");

            try {
                // Intentar con verificación SSL
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $this->info("✅ URL funciona: {$url}");
                    $this->info("📊 Tamaño: " . number_format(strlen($response->body())) . " bytes");
                    $this->info("📋 Content-Type: " . ($response->header('Content-Type') ?? 'No especificado'));
                    $workingUrls[] = [
                        'url' => $url,
                        'size' => strlen($response->body()),
                        'content_type' => $response->header('Content-Type'),
                        'status' => $response->status()
                    ];
                } else {
                    $this->warn("⚠️ HTTP {$response->status()}: {$url}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error: {$e->getMessage()}");

                // Intentar sin verificación SSL
                try {
                    $response = Http::timeout(10)->withoutVerifying()->get($url);

                    if ($response->successful()) {
                        $this->info("✅ URL funciona (sin SSL): {$url}");
                        $this->info("📊 Tamaño: " . number_format(strlen($response->body())) . " bytes");
                        $workingUrls[] = [
                            'url' => $url,
                            'size' => strlen($response->body()),
                            'content_type' => $response->header('Content-Type'),
                            'status' => $response->status(),
                            'ssl_bypass' => true
                        ];
                    } else {
                        $this->warn("⚠️ HTTP {$response->status()} (sin SSL): {$url}");
                    }
                } catch (\Exception $e2) {
                    $this->error("❌ Error (sin SSL): {$e2->getMessage()}");
                }
            }

            $this->newLine();
        }

        // Mostrar resumen
        if (!empty($workingUrls)) {
            $this->info('📊 URLs que funcionan:');
            $this->newLine();

            foreach ($workingUrls as $index => $urlInfo) {
                $this->info(($index + 1) . ". {$urlInfo['url']}");
                $this->line("   Tamaño: " . number_format($urlInfo['size']) . " bytes");
                $this->line("   Content-Type: " . ($urlInfo['content_type'] ?? 'No especificado'));
                $this->line("   Status: {$urlInfo['status']}");
                if (isset($urlInfo['ssl_bypass'])) {
                    $this->line("   SSL: Bypass requerido");
                }
                $this->newLine();
            }

            // Sugerir la mejor URL
            $bestUrl = $this->selectBestUrl($workingUrls);
            $this->info("🎯 URL recomendada: {$bestUrl}");

            $this->info('💡 Para actualizar el servicio, modifica la URL en:');
            $this->line('   api/app/Services/ICLExcelImportService.php');
            $this->line('   Línea: private const EXCEL_URLS = [');
            $this->line("   Cambia 'indec' => '{$bestUrl}'");

        } else {
            $this->error('❌ No se encontraron URLs que funcionen');
            $this->info('💡 Sugerencias:');
            $this->line('   • Verificar conectividad de red');
            $this->line('   • Revisar si el INDEC cambió las URLs');
            $this->line('   • Usar archivo local con --file=');
        }

        return 0;
    }

    /**
     * Seleccionar la mejor URL basada en tamaño y tipo de contenido
     */
    private function selectBestUrl(array $workingUrls): string
    {
        // Priorizar URLs con contenido Excel
        $excelUrls = array_filter($workingUrls, function($url) {
            $contentType = strtolower($url['content_type'] ?? '');
            return strpos($contentType, 'excel') !== false ||
                   strpos($contentType, 'spreadsheet') !== false ||
                   strpos($contentType, 'application/vnd.ms-excel') !== false ||
                   strpos($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false;
        });

        if (!empty($excelUrls)) {
            // Ordenar por tamaño (mayor = más datos)
            usort($excelUrls, function($a, $b) {
                return $b['size'] - $a['size'];
            });
            return $excelUrls[0]['url'];
        }

        // Si no hay URLs con tipo Excel, usar la de mayor tamaño
        usort($workingUrls, function($a, $b) {
            return $b['size'] - $a['size'];
        });

        return $workingUrls[0]['url'];
    }
}
