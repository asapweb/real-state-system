<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::where('name', 'Argentina')->first();

        if ($country) {
            $states = [
                ['id' => 6 ,'name' => 'Buenos Aires', 'is_default' => true],
                ['id' => 2 ,'name' => 'Ciudad Autónoma de Buenos Aires', 'is_default' => false],
                ['id' => 10 ,'name' => 'Catamarca', 'is_default' => false],
                ['id' => 22 ,'name' => 'Chaco', 'is_default' => false],
                ['id' => 26 ,'name' => 'Chubut', 'is_default' => false],
                ['id' => 14 ,'name' => 'Córdoba', 'is_default' => false],
                ['id' => 18 ,'name' => 'Corrientes', 'is_default' => false],
                ['id' => 30 ,'name' => 'Entre Ríos', 'is_default' => false],
                ['id' => 34 ,'name' => 'Formosa', 'is_default' => false],
                ['id' => 38 ,'name' => 'Jujuy', 'is_default' => false],
                ['id' => 42 ,'name' => 'La Pampa', 'is_default' => false],
                ['id' => 46 ,'name' => 'La Rioja', 'is_default' => false],
                ['id' => 50 ,'name' => 'Mendoza', 'is_default' => false],
                ['id' => 54 ,'name' => 'Misiones', 'is_default' => false],
                ['id' => 58 ,'name' => 'Neuquén', 'is_default' => false],
                ['id' => 62 ,'name' => 'Río Negro', 'is_default' => false],
                ['id' => 66 ,'name' => 'Salta', 'is_default' => false],
                ['id' => 70 ,'name' => 'San Juan', 'is_default' => false],
                ['id' => 74 ,'name' => 'San Luis', 'is_default' => false],
                ['id' => 78 ,'name' => 'Santa Cruz', 'is_default' => false],
                ['id' => 82 ,'name' => 'Santa Fe', 'is_default' => false],
                ['id' => 86 ,'name' => 'Santiago del Estero', 'is_default' => false],
                ['id' => 94 ,'name' => 'Tierra del Fuego', 'is_default' => false],
                ['id' => 90 ,'name' => 'Tucumán', 'is_default' => false],
            ];

            foreach ($states as $state) {
                State::updateOrCreate(
                    ['id' => $state['id'],'name' => $state['name'], 'country_id' => $country->id],
                    ['is_default' => $state['is_default']]
                );
            }
        }
    }
}
