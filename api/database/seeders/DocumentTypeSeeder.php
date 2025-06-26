<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'CUIT', 'code_afip' => '80', 'is_default' => false],
            ['name' => 'CUIL', 'code_afip' => '86', 'is_default' => false],
            ['name' => 'CDI', 'code_afip' => '87', 'is_default' => false],
            ['name' => 'LE', 'code_afip' => '89', 'is_default' => false],
            ['name' => 'LC', 'code_afip' => '90', 'is_default' => false],
            ['name' => 'CI Extranjera', 'code_afip' => '91', 'is_default' => false],
            ['name' => 'en trámite', 'code_afip' => '92', 'is_default' => false],
            ['name' => 'Acta nacimiento', 'code_afip' => '93', 'is_default' => false],
            ['name' => 'Pasaporte', 'code_afip' => '94', 'is_default' => false],
            ['name' => 'CI Policía Federal', 'code_afip' => '95', 'is_default' => false],
            ['name' => 'DNI', 'code_afip' => '96', 'is_default' => true],
            ['name' => 'Otro', 'code_afip' => '99', 'is_default' => false],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(
                ['name' => $type['name']],
                ['code_afip' => $type['code_afip'], 'is_default' => $type['is_default']]
            );
        }
    }
}
