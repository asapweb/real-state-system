<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CollectionGenerationService;
use App\Models\Collection;
use Carbon\Carbon;

class CollectionsBenchmark extends Command
{
    protected $signature = 'collections:benchmark
                            {--month=2025-07 : Mes en formato YYYY-MM}';

    protected $description = 'Mide el tiempo de ejecución de generación de cobranzas para el mes indicado';

    public function handle()
    {
        $period = Carbon::createFromFormat('Y-m', $this->option('month'));

        $this->info("Generando cobranzas para {$period->format('F Y')}...");
        $start = microtime(true);

        $count = (new CollectionGenerationService)->generateForMonth($period);

        $duration = round(microtime(true) - $start, 3);
        $this->info("✔ {$count} cobranzas generadas en {$duration} segundos.");
        $this->line('');

        // Desglose por moneda
        $this->info('Desglose por moneda:');
        $results = Collection::where('period', $period->format('Y-m'))
            ->selectRaw('currency, COUNT(*) as total, SUM(total_amount) as sum')
            ->groupBy('currency')
            ->get();

        foreach ($results as $row) {
            $totalFormatted = number_format($row->sum, 2, ',', '.');
            $this->line("- {$row->currency}: {$row->total} cobranzas · Total: {$totalFormatted} {$row->currency}");
        }
    }
}
