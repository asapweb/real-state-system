<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndexTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'ipc', 'name' => 'IPC INDEC'],
            ['code' => 'uva', 'name' => 'UVA BCRA'],
            ['code' => 'cvs', 'name' => 'Coeficiente de Variación Salarial (CVS)'],
            ['code' => 'ripte', 'name' => 'RIPTE'],
        ];

        foreach ($data as $item) {
            DB::table('index_types')->updateOrInsert(
                ['code' => $item['code']],
                ['name' => $item['name'], 'is_active' => true]
            );
        }
    }
}
