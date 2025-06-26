<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxCondition;

class TaxConditionSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            ['name' => 'IVA Responsable Inscripto', 'code_afip' => '1', 'is_default' => false],
            ['name' => 'IVA Responsable no Inscripto', 'code_afip' => '2', 'is_default' => false],
            ['name' => 'IVA no Responsable', 'code_afip' => '3', 'is_default' => false],
            ['name' => 'IVA Sujeto Exento', 'code_afip' => '4', 'is_default' => false],
            ['name' => 'No Categorizado', 'code_afip' => '5', 'is_default' => false],
            ['name' => 'Monotributista', 'code_afip' => '6', 'is_default' => false],
            ['name' => 'Sujeto no alcanzado', 'code_afip' => '7', 'is_default' => false],
            ['name' => 'Sujeto no Categorizado', 'code_afip' => '8', 'is_default' => false],
            ['name' => 'Proveedor del Exterior', 'code_afip' => '9', 'is_default' => false],
            ['name' => 'Cliente del Exterior', 'code_afip' => '10', 'is_default' => false],
            ['name' => 'IVA Liberado – Ley Nº 19.640', 'code_afip' => '11', 'is_default' => false],
            ['name' => 'IVA Responsable Inscripto – Agente de Percepción', 'code_afip' => '12', 'is_default' => false],
            ['name' => 'Pequeño Contribuyente Eventual', 'code_afip' => '13', 'is_default' => false],
            ['name' => 'Monotributista Social', 'code_afip' => '14', 'is_default' => false],
            ['name' => 'Pequeño Contribuyente Eventual Social', 'code_afip' => '15', 'is_default' => false],
            ['name' => 'Consumidor Final', 'code_afip' => '16', 'is_default' => true],
        ];

        foreach ($conditions as $condition) {
            TaxCondition::updateOrCreate(
                ['name' => $condition['name']],
                ['code_afip' => $condition['code_afip'], 'is_default' => $condition['is_default']]
            );
        }
    }
}
