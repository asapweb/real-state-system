<?php

namespace App\Console\Commands;

use App\Models\IndexType;
use Illuminate\Console\Command;

class CreateUVAIndexTypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:create-uva-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear el tipo de índice UVA si no existe';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando tipo de índice UVA...');

        // Verificar si ya existe
        $existingType = IndexType::where('code', 'UVA')->first();

        if ($existingType) {
            $this->info("✅ El tipo de índice UVA ya existe:");
            $this->info("   Código: {$existingType->code}");
            $this->info("   Nombre: {$existingType->name}");
            $this->info("   Modo de cálculo: {$existingType->calculation_mode}");
            $this->info("   Activo: " . ($existingType->is_active ? 'Sí' : 'No'));
            return 0;
        }

        // Crear el tipo de índice
        $this->info('📝 Creando tipo de índice UVA...');

        try {
            $uvaType = IndexType::create([
                'code' => 'UVA',
                'name' => 'Unidad de Valor Adquisitivo',
                'is_active' => true,
                'calculation_mode' => 'ratio',
            ]);

            $this->info('✅ Tipo de índice UVA creado exitosamente:');
            $this->info("   Código: {$uvaType->code}");
            $this->info("   Nombre: {$uvaType->name}");
            $this->info("   Modo de cálculo: {$uvaType->calculation_mode}");
            $this->info("   Activo: " . ($uvaType->is_active ? 'Sí' : 'No'));

            $this->info('🚀 Ahora puedes ejecutar: php artisan indices:import-uva-excel');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error creando tipo de índice: {$e->getMessage()}");
            return 1;
        }
    }
}
