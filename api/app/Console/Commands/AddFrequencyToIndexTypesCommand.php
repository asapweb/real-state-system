<?php

namespace App\Console\Commands;

use App\Models\IndexType;
use App\Enums\IndexFrequency;
use Illuminate\Console\Command;

class AddFrequencyToIndexTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:add-frequency {--dry-run : Ejecutar en modo simulación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agregar el campo frequency a los tipos de índice existentes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando tipos de índice existentes...');

        $indexTypes = IndexType::all();

        if ($indexTypes->isEmpty()) {
            $this->warn('⚠️ No se encontraron tipos de índice. Ejecuta primero: php artisan db:seed --class=IndexTypeSeeder');
            return 1;
        }

        $this->info("📋 Tipos de índice encontrados: {$indexTypes->count()}");

        foreach ($indexTypes as $indexType) {
            $mode = $indexType->calculation_mode instanceof \BackedEnum ? $indexType->calculation_mode->value : $indexType->calculation_mode;
            $frequency = $indexType->frequency instanceof \BackedEnum ? $indexType->frequency->value : ($indexType->frequency ?? 'daily');
            $this->info("   - {$indexType->code}: {$indexType->name} (modo: {$mode}, frecuencia: {$frequency})");
        }

        // Actualizar frecuencias según el tipo de índice
        $this->info('🔄 Actualizando frecuencias...');

        $dailyIndices = ['ICL', 'ICP', 'UVA'];
        $monthlyIndices = ['IPC', 'CREEBBA'];

        foreach ($indexTypes as $indexType) {
            $expectedFrequency = null;

            if (in_array($indexType->code, $dailyIndices)) {
                $expectedFrequency = IndexFrequency::DAILY;
            } elseif (in_array($indexType->code, $monthlyIndices)) {
                $expectedFrequency = IndexFrequency::MONTHLY;
            } else {
                // Por defecto, usar daily
                $expectedFrequency = IndexFrequency::DAILY;
            }

            $currentFrequency = $indexType->frequency instanceof \BackedEnum ? $indexType->frequency->value : ($indexType->frequency ?? 'daily');
            $expectedFrequencyValue = $expectedFrequency->value;

            if ($currentFrequency !== $expectedFrequencyValue) {
                $this->info("📝 Actualizando {$indexType->code}: {$currentFrequency} → {$expectedFrequencyValue}");

                if (!$this->option('dry-run')) {
                    try {
                        $indexType->update(['frequency' => $expectedFrequency]);
                        $this->info("✅ {$indexType->code} actualizado exitosamente");
                    } catch (\Exception $e) {
                        $this->error("❌ Error actualizando {$indexType->code}: {$e->getMessage()}");
                        return 1;
                    }
                } else {
                    $this->info("🔍 [DRY RUN] Se actualizaría {$indexType->code}: {$currentFrequency} → {$expectedFrequencyValue}");
                }
            } else {
                $this->info("✅ {$indexType->code} ya tiene la frecuencia correcta: {$currentFrequency}");
            }
        }

        $this->info('🎉 Proceso completado exitosamente');
        $this->info('📚 Documentación:');
        $this->info('   - Frecuencia DAILY: Para índices con valores por fecha específica (ICL, ICP, UVA)');

        return 0;
    }
}
