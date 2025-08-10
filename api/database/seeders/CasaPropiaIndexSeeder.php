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
            ['code' => 'CVS'],
            [
                'name' => 'Casa Propia',
                'calculation_mode' => 'ratio',
            ]
        );

        // Datos extraídos del PDF
        $data = [
            ['period' => '2023-03-01', 'value' => 1.0490],
            ['period' => '2023-04-01', 'value' => 1.0504],
            ['period' => '2023-05-01', 'value' => 1.0505],
            ['period' => '2023-06-01', 'value' => 1.0522],
            ['period' => '2023-07-01', 'value' => 1.0557],
            ['period' => '2023-08-01', 'value' => 1.0558],
            ['period' => '2023-09-01', 'value' => 1.0571],
            ['period' => '2023-10-01', 'value' => 1.0576],
            ['period' => '2023-11-01', 'value' => 1.0628],
            ['period' => '2023-12-01', 'value' => 1.0637],
            ['period' => '2024-01-01', 'value' => 1.0689],
            ['period' => '2024-02-01', 'value' => 1.0708],
            ['period' => '2024-03-01', 'value' => 1.0727],
            ['period' => '2024-04-01', 'value' => 1.0749],
            ['period' => '2024-05-01', 'value' => 1.0859],
            ['period' => '2024-06-01', 'value' => 1.0920],
            ['period' => '2024-07-01', 'value' => 1.0921],
            ['period' => '2024-08-01', 'value' => 1.0939],
            ['period' => '2024-09-01', 'value' => 1.0924],
            ['period' => '2024-10-01', 'value' => 1.0890],
            ['period' => '2024-11-01', 'value' => 1.0858],
            ['period' => '2024-12-01', 'value' => 1.0820],
            ['period' => '2025-01-01', 'value' => 1.0708],
            ['period' => '2025-02-01', 'value' => 1.0719],
            ['period' => '2025-03-01', 'value' => 1.0671],
            ['period' => '2025-04-01', 'value' => 1.0622],
            ['period' => '2025-05-01', 'value' => 1.0545],
            ['period' => '2025-06-01', 'value' => 1.0491],
            ['period' => '2025-07-01', 'value' => 1.0440],
            ['period' => '2025-08-01', 'value' => 1.0385],
        ];

        foreach ($data as $entry) {
            IndexValue::updateOrCreate(
                [
                    'index_type_id' => $indexType->id,
                    'effective_date' => $entry['period'],
                ],
                [
                    'value' => $entry['value'],
                ]
            );
        }
    }
}
