<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IndexType;
use App\Models\IndexValue;

class IndexValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('index_values')->truncate();
        // Obtener tipos de índice
        $ipc = IndexType::where('code', 'IPC')->first();
        $creebba = IndexType::where('code', 'CREEBBA')->first();
        $icl = IndexType::where('code', 'ICL')->first();
        $igj = IndexType::where('code', 'IGJ')->first();

        if ($ipc) {
            // Valores de ejemplo para IPC (percentage mode)
            IndexValue::create([
                'index_type_id' => $ipc->id,
                'period' => '2025-01',
                'value' => 2.5,
            ]);

            IndexValue::create([
                'index_type_id' => $ipc->id,
                'period' => '2025-02',
                'value' => 2.8,
            ]);

            IndexValue::create([
                'index_type_id' => $ipc->id,
                'period' => '2025-03',
                'value' => 3.1,
            ]);
        }

        if ($creebba) {
            // Valores de ejemplo para CREEBBA (percentage mode)
            IndexValue::create([
                'index_type_id' => $creebba->id,
                'period' => '2025-01',
                'value' => 1.8,
            ]);

            IndexValue::create([
                'index_type_id' => $creebba->id,
                'period' => '2025-02',
                'value' => 2.0,
            ]);
        }

        if ($icl) {
            // Valores de ejemplo para ICL (ratio mode)
            IndexValue::create([
                'index_type_id' => $icl->id,
                'date' => '2025-01-01',
                'value' => 1234.5678,
            ]);

            IndexValue::create([
                'index_type_id' => $icl->id,
                'date' => '2025-02-01',
                'value' => 1245.6789,
            ]);

            IndexValue::create([
                'index_type_id' => $icl->id,
                'date' => '2025-03-01',
                'value' => 1256.7890,
            ]);
        }

        if ($igj) {
            // Valores de ejemplo para IGJ (ratio mode)
            IndexValue::create([
                'index_type_id' => $igj->id,
                'date' => '2025-01-01',
                'value' => 987.6543,
            ]);

            IndexValue::create([
                'index_type_id' => $igj->id,
                'date' => '2025-02-01',
                'value' => 998.7654,
            ]);
        }
    }
}
