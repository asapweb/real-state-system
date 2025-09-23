<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:clean
                            {--force : Force execution even in production}
                            {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all demo data from the database';

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

        // Confirmar operación
        if (!$this->option('confirm')) {
            if (!$this->confirm('⚠️  ¿Estás seguro de eliminar todos los datos de demo?')) {
                $this->info('Operación cancelada');
                return 0;
            }
        }

        $this->info('🧹 Limpiando datos de demo...');

        try {
            // Tablas en orden de dependencias (hijo -> padre)
            $tables = [
                'voucher_payments',
                'voucher_applications',
                'voucher_associations',
                'voucher_items',
                'vouchers',
                'contract_charges',
                'contract_adjustments',
                'contract_clients',
                'contract_expenses',
                'contracts',
                'clients',
                'properties'
            ];

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $this->line("   • Limpiando {$table} ({$count} registros)");
                    DB::table($table)->truncate();
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('✅ Datos de demo eliminados exitosamente');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error cleaning demo data: ' . $e->getMessage());
            return 1;
        }
    }
}
