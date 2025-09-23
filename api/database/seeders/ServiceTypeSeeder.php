<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['code' => 'WATER',                 'name' => 'Agua',                       'is_active' => true],
            ['code' => 'GAS',                   'name' => 'Gas',                        'is_active' => true],
            ['code' => 'ELECTRICITY',           'name' => 'Electricidad',               'is_active' => true],
            ['code' => 'CONDO_FEES',            'name' => 'Expensas',                   'is_active' => true],
            ['code' => 'CONDO_EXTRAORDINARY',   'name' => 'Expensas extraordinarias',   'is_active' => true],
            ['code' => 'INSURANCE',             'name' => 'Seguro',                     'is_active' => true],
            ['code' => 'INTERNET',              'name' => 'Internet',                   'is_active' => true],
            ['code' => 'MUNICIPAL_TAX',         'name' => 'Impuesto municipal',         'is_active' => true],
            ['code' => 'SEWER',                 'name' => 'Cloacas / saneamiento',      'is_active' => true],
            ['code' => 'MAINTENANCE',           'name' => 'Mantenimiento',              'is_active' => true],
        ];

        foreach ($rows as $r) {
            DB::table('service_types')->updateOrInsert(
                ['code' => $r['code']],
                [
                    'name'       => $r['name'],
                    'is_active'  => $r['is_active'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
