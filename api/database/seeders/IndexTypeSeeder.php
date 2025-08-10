<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IndexType;
use App\Enums\CalculationMode;
use App\Enums\IndexFrequency;

class IndexTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        IndexType::updateOrCreate(
            ['code' => 'CREEBBA'],
            [
                'name' => 'Índice CREEBBA',
                'is_active' => true,
                'calculation_mode' => CalculationMode::RATIO,
                'frequency' => IndexFrequency::MONTHLY,
            ]
        );

        // Índices de tipo ratio (ICL, etc.)
        IndexType::updateOrCreate(
            ['code' => 'ICL'],
            [
                'name' => 'Índice de Costo de la Construcción',
                'is_active' => true,
                'calculation_mode' => CalculationMode::RATIO,
                'frequency' => IndexFrequency::DAILY,
            ]
        );

        IndexType::updateOrCreate(
            ['code' => 'UVA'],
            [
                'name' => 'Unidad de Valor Adquisitivo',
                'is_active' => true,
                'calculation_mode' => CalculationMode::RATIO,
                'frequency' => IndexFrequency::DAILY,
            ]
        );

        IndexType::updateOrCreate(
            ['code' => 'CVS'],
            [
                'name' => 'Casa Propia',
                'is_active' => true,
                'calculation_mode' => CalculationMode::RATIO,
                'frequency' => IndexFrequency::MONTHLY,
            ]
        );
    }
}
