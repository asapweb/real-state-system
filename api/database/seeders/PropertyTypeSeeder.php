<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Casa', 'is_default' => false],
            ['name' => 'Departamento', 'is_default' => true],
            ['name' => 'PH', 'is_default' => false],
            ['name' => 'Cochera', 'is_default' => false],
            ['name' => 'Galpón', 'is_default' => false],
            ['name' => 'Local', 'is_default' => false],
            ['name' => 'Lote', 'is_default' => false],
        ];

        foreach ($types as $type) {
            PropertyType::updateOrCreate(
                ['name' => $type['name']],
                ['is_default' => $type['is_default']]
            );
        }
    }
}
