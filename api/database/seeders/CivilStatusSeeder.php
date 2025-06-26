<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CivilStatus;

class CivilStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Soltero/a', 'is_default' => true],
            ['name' => 'Casado/a', 'is_default' => false],
            ['name' => 'Divorciado/a', 'is_default' => false],
            ['name' => 'Viudo/a', 'is_default' => false],
            ['name' => 'Unión convivencial', 'is_default' => false],
            ['name' => 'Separado/a', 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            CivilStatus::updateOrCreate(
                ['name' => $status['name']],
                ['is_default' => $status['is_default']]
            );
        }
    }
}
