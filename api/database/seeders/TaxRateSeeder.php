<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $taxRates = [
            ['name' => 'Exento',       'rate' => 0.00, 'included_in_vat_detail' => false],
            ['name' => 'No Gravado',   'rate' => 0.00, 'included_in_vat_detail' => false],
            ['name' => '0%',           'rate' => 0.00, 'included_in_vat_detail' => false],
            ['name' => '10.5%',        'rate' => 10.5, 'included_in_vat_detail' => true],
            ['name' => '21%',          'rate' => 21.0, 'included_in_vat_detail' => true],
            ['name' => '27%',          'rate' => 27.0, 'included_in_vat_detail' => true],
        ];

        foreach ($taxRates as $i => $data) {
            DB::table('tax_rates')->updateOrInsert(
                ['name' => $data['name']],
                array_merge($data, ['is_default' => $data['name'] === '21%', 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
