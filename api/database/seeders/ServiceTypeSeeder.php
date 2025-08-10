<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'rent', 'name' => 'Alquiler mensual'],
            ['code' => 'expensas_ordinarias', 'name' => 'Expensas ordinarias'],
            ['code' => 'expensas_extraordinarias', 'name' => 'Expensas extraordinarias'],
            ['code' => 'agua', 'name' => 'Agua'],
            ['code' => 'luz', 'name' => 'Luz'],
            ['code' => 'gas', 'name' => 'Gas'],
            ['code' => 'internet', 'name' => 'Internet / Cable'],
            ['code' => 'abl', 'name' => 'Impuesto municipal (ABL)'],
            ['code' => 'inmobiliario', 'name' => 'Impuesto inmobiliario'],
            ['code' => 'rentas', 'name' => 'Rentas provinciales'],
            ['code' => 'seguro_inmueble', 'name' => 'Seguro de inmueble'],
            ['code' => 'seguro_contenido', 'name' => 'Seguro de contenido'],
            ['code' => 'mantenimiento_menor', 'name' => 'Mantenimiento menor'],
            ['code' => 'mantenimiento_mayor', 'name' => 'Mantenimiento mayor'],
            ['code' => 'gastos_administrativos', 'name' => 'Gastos administrativos'],
            ['code' => 'penalidades', 'name' => 'Penalidades contractuales'],
            ['code' => 'otros', 'name' => 'Otros'],
        ];

        foreach ($types as $type) {
            ServiceType::updateOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name']]
            );
        }
    }
}
