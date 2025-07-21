<?php

namespace Database\Seeders;

use App\Models\IndexValue;
use App\Models\IndexType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CasaPropiaIndexSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener o crear el tipo de índice "Casa Propia"
        $indexType = IndexType::firstOrCreate(
            ['name' => 'Casa Propia'],
            [
                'calculation_mode' => 'ratio',
                'description' => 'Índice CVS / CER Casa Propia',
            ]
        );

        // Datos extraídos del PDF
        $data = [
            ['period' => '2023-03', 'value' => 1.0490],
            ['period' => '2023-04', 'value' => 1.0504],
            ['period' => '2023-05', 'value' => 1.0505],
            ['period' => '2023-06', 'value' => 1.0522],
            ['period' => '2023-07', 'value' => 1.0557],
            ['period' => '2023-08', 'value' => 1.0558],
            ['period' => '2023-09', 'value' => 1.0571],
            ['period' => '2023-10', 'value' => 1.0576],
            ['period' => '2023-11', 'value' => 1.0628],
            ['period' => '2023-12', 'value' => 1.0637],
            ['period' => '2024-01', 'value' => 1.0689],
            ['period' => '2024-02', 'value' => 1.0708],
            ['period' => '2024-03', 'value' => 1.0727],
            ['period' => '2024-04', 'value' => 1.0749],
            ['period' => '2024-05', 'value' => 1.0859],
            ['period' => '2024-06', 'value' => 1.0920],
            ['period' => '2024-07', 'value' => 1.0921],
            ['period' => '2024-08', 'value' => 1.0939],
            ['period' => '2024-09', 'value' => 1.0924],
            ['period' => '2024-10', 'value' => 1.0890],
            ['period' => '2024-11', 'value' => 1.0858],
            ['period' => '2024-12', 'value' => 1.0820],
            ['period' => '2025-01', 'value' => 1.0708],
            ['period' => '2025-02', 'value' => 1.0719],
            ['period' => '2025-03', 'value' => 1.0671],
            ['period' => '2025-04', 'value' => 1.0622],
            ['period' => '2025-05', 'value' => 1.0545],
            ['period' => '2025-06', 'value' => 1.0491],
            ['period' => '2025-07', 'value' => 1.0440],
            ['period' => '2025-08', 'value' => 1.0385],
        ];

        foreach ($data as $entry) {
            IndexValue::updateOrCreate(
                [
                    'index_type_id' => $indexType->id,
                    'period' => $entry['period'],
                ],
                [
                    'value' => $entry['value'],
                ]
            );
        }
    }
}
