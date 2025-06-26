<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $configurations = [
            [
                'group' => 'general',
                'key' => 'default_currency',
                'value' => 'ARS',
            ],
            [
                'group' => 'afip',
                'key' => 'point_of_sale',
                'value' => '1',
            ],
            [
                'group' => 'afip',
                'key' => 'invoice_type',
                'value' => 'C', // Factura tipo C por defecto
            ],
            [
                'group' => 'portal',
                'key' => 'enable_notifications',
                'value' => 'true',
            ],
        ];

        foreach ($configurations as $config) {
            Configuration::updateOrCreate(
                [
                    'group' => $config['group'],
                    'key' => $config['key'],
                ],
                [
                    'value' => $config['value'],
                ]
            );
        }
    }
}
