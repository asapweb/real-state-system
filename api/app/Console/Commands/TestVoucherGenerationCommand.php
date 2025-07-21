<?php

namespace App\Console\Commands;

use App\Services\VoucherGenerationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestVoucherGenerationCommand extends Command
{
    protected $signature = 'test:voucher-generation {period? : Periodo en formato Y-m (ej: 2024-01)} {--dry-run : Solo preview sin generar}';
    protected $description = 'Prueba la generación de vouchers de cobranza';

    public function handle(VoucherGenerationService $service): int
    {
        $period = $this->argument('period');

        if (!$period) {
            $period = now()->format('Y-m');
        }

        $this->info("🔍 Probando generación de vouchers para período: {$period}");
        $this->newLine();

        try {
            $carbonPeriod = Carbon::createFromFormat('Y-m', $period);

            if ($this->option('dry-run')) {
                $this->info('📊 Ejecutando preview...');
                $preview = $service->previewForMonth($carbonPeriod);

                $this->info('📈 Resultados del preview:');
                $this->info("- Período: {$preview['period']}");
                $this->info("- Total contratos: {$preview['total_contracts']}");
                $this->info("- Ya generados: {$preview['already_generated']}");
                $this->info("- Pendientes de generación: {$preview['pending_generation']}");
                $this->info("- Bloqueados por meses faltantes: {$preview['blocked_due_to_missing_months']}");
                $this->info("- Bloqueados por períodos previos pendientes: {$preview['blocked_due_to_previous_pending']}");
                $this->info("- Estado: {$preview['status']}");

                if (!empty($preview['blocked_contracts'])) {
                    $this->newLine();
                    $this->warn('⚠️  Contratos bloqueados:');
                    foreach ($preview['blocked_contracts'] as $contract) {
                        $this->warn("- ID: {$contract['id']}, Cliente: {$contract['name']}, Razón: {$contract['reason']}");
                    }
                }
            } else {
                $this->info('🚀 Generando vouchers...');
                $generated = $service->generateForMonth($carbonPeriod);

                $this->info("✅ Se generaron {$generated->count()} vouchers");

                foreach ($generated as $voucher) {
                    $this->info("- Voucher ID: {$voucher->id}, Cliente: {$voucher->client->name}, Total: {$voucher->currency} {$voucher->total}");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
