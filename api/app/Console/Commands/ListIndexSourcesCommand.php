<?php

namespace App\Console\Commands;

use App\Services\ICLExcelImportService;
use Illuminate\Console\Command;

class ListIndexSourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:list-sources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar todas las fuentes de índices disponibles';

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
        $this->info('📊 Fuentes de índices disponibles:');
        $this->newLine();

        $sources = $this->excelImportService->getAvailableSources();

        foreach ($sources as $key => $source) {
            $this->info("🔗 {$key}:");
            $this->line("   📡 Nombre: {$source['name']}");
            $this->line("   🔗 URL: {$source['url']}");
            $this->line("   📝 Descripción: {$source['description']}");
            $this->newLine();
        }

        $this->info('📋 Comandos disponibles:');
        $this->line('   • indices:import-icl-excel - Importar ICL desde BCRA');
        $this->line('   • indices:import-icp-excel - Importar ICP desde INDEC');
        $this->line('   • indices:test-icp-connection - Probar conexión ICP');
        $this->line('   • indices:list-sources - Listar fuentes (este comando)');

        return 0;
    }
}
