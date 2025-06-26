<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $puestos = [
            'caja' => 'Caja',
            'contratos' => 'Contratos',
            'cobro_y_liquidaciones' => 'Cobro y liquidaciones',
            'recepcion' => 'Recepción',
            'gestiones_de_mantenimiento' => 'Gestiones de mantenimiento',
            'ventas' => 'Ventas',
            'gerencia' => 'Gerencia',
        ];

        foreach ($puestos as $clave => $nombre) {
            Department::updateOrCreate(
                ['name' => $nombre], // Busca por el nombre
                ['name' => $nombre]  // Crea o actualiza con el nombre
            );
        }
    }
}
