<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DemoRealStateSeeder;

class SeedDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed
                            {--force : Force execution even in production}
                            {--clean : Clean all demo data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with demo real estate data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar entorno de producción
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ No se puede ejecutar en producción sin --force');
            return 1;
        }

        // Confirmar en producción
        if (app()->environment('production') && $this->option('force')) {
            if (!$this->confirm('⚠️  ¿Estás seguro de ejecutar en producción?')) {
                $this->info('Operación cancelada');
                return 0;
            }
        }

        // Limpiar datos si se solicita
        if ($this->option('clean')) {
            $this->info('🧹 Limpiando datos de demo...');
            $this->call('demo:clean');
        }

        // Ejecutar seeder
        $this->info('🌱 Ejecutando DemoRealStateSeeder...');

        try {
            $seeder = new DemoRealStateSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->info('✅ Demo data seeded successfully!');
            $this->newLine();

            // Mostrar resumen
            $this->showSummary();

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error seeding demo data: ' . $e->getMessage());
            return 1;
        }
    }

    private function showSummary(): void
    {
        $this->info('📊 Resumen de datos creados:');

        $tables = [
            'Contract' => \App\Models\Contract::class,
            'Client' => \App\Models\Client::class,
            'Property' => \App\Models\Property::class,
            'ContractAdjustment' => \App\Models\ContractAdjustment::class,
            'ContractCharge' => \App\Models\ContractCharge::class,
            'Voucher' => \App\Models\Voucher::class,
        ];

        foreach ($tables as $name => $model) {
            $count = $model::count();
            $this->line("   • {$name}: {$count}");
        }

        // Estadísticas adicionales
        $this->newLine();
        $this->info('📈 Estadísticas adicionales:');

        $adjustmentStats = \App\Models\ContractAdjustment::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        foreach ($adjustmentStats as $stat) {
            $this->line("   • Ajustes {$stat->type->value}: {$stat->count}");
        }

        $voucherStats = \App\Models\Voucher::selectRaw('voucher_type_id, COUNT(*) as count')
            ->with('voucherType')
            ->groupBy('voucher_type_id')
            ->get();

        foreach ($voucherStats as $stat) {
            $typeName = $stat->voucherType->short_name ?? 'Unknown';
            $this->line("   • Vouchers {$typeName}: {$stat->count}");
        }
    }
}

