<?php

namespace App\Console\Commands;

use App\Services\CollectionMigrationService;
use Illuminate\Console\Command;

class MigrateCollectionsToVouchersCommand extends Command
{
    protected $signature = 'collections:migrate-to-vouchers {--dry-run : Ejecutar sin hacer cambios}';
    protected $description = 'Migra todas las collections existentes a vouchers con tipo COB';

    public function handle(CollectionMigrationService $migrationService): int
    {
        $this->info('Iniciando migración de collections a vouchers...');

        if ($this->option('dry-run')) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios en la base de datos');
        }

        try {
            $results = $migrationService->migrateAllCollections();

            $this->info("Migración completada:");
            $this->info("- Collections migradas: {$results['migrated']}");
            $this->info("- Errores: " . count($results['errors']));
            $this->info("- Omitidas: {$results['skipped']}");

            if (!empty($results['errors'])) {
                $this->error('Errores encontrados:');
                foreach ($results['errors'] as $error) {
                    $this->error("- Collection ID {$error['collection_id']}: {$error['error']}");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error durante la migración: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
