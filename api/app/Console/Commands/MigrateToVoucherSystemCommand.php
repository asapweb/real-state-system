<?php

namespace App\Console\Commands;

use App\Services\CollectionMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToVoucherSystemCommand extends Command
{
    protected $signature = 'system:migrate-to-vouchers {--dry-run : Ejecutar sin hacer cambios} {--force : Forzar migración sin confirmación}';
    protected $description = 'Migra completamente el sistema de collections a vouchers';

    public function handle(CollectionMigrationService $migrationService): int
    {
        $this->info('🚀 Iniciando migración completa del sistema de collections a vouchers...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Verificar que existe un booklet compatible
        $booklet = \App\Models\Booklet::whereHas('voucherType', function ($query) {
            $query->where('code', 'COB');
        })->first();

        if (!$booklet) {
            $this->error('❌ No se encontró un booklet compatible con tipo COB');
            $this->error('   Debes crear un booklet con voucher_type_id = COB antes de continuar');
            return self::FAILURE;
        }

        $this->info("✅ Booklet encontrado: {$booklet->name} (ID: {$booklet->id})");
        $this->newLine();

        // Contar collections existentes
        $collectionsCount = \App\Models\Collection::count();
        $this->info("📊 Collections existentes: {$collectionsCount}");

        if ($collectionsCount === 0) {
            $this->info('✅ No hay collections para migrar');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('¿Estás seguro de que quieres proceder con la migración?')) {
                $this->info('❌ Migración cancelada');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('🔄 Ejecutando migración...');

        try {
            DB::beginTransaction();

            $results = $migrationService->migrateAllCollections();

            if (!$this->option('dry-run')) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $this->newLine();
            $this->info('📈 Resultados de la migración:');
            $this->info("- ✅ Collections migradas: {$results['migrated']}");
            $this->info("- ❌ Errores: " . count($results['errors']));
            $this->info("- ⏭️  Omitidas: {$results['skipped']}");

            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error('❌ Errores encontrados:');
                foreach ($results['errors'] as $error) {
                    $this->error("- Collection ID {$error['collection_id']}: {$error['error']}");
                }
            }

            if ($results['migrated'] > 0 && !$this->option('dry-run')) {
                $this->newLine();
                $this->info('🎉 Migración completada exitosamente!');
                $this->info('💡 Próximos pasos:');
                $this->info('   1. Verificar que los vouchers se crearon correctamente');
                $this->info('   2. Actualizar el frontend para usar /api/vouchers');
                $this->info('   3. Ejecutar: php artisan migrate (para eliminar tablas obsoletas)');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error durante la migración: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
