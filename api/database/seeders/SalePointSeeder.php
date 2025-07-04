<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalePointSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sale_points')->updateOrInsert(
            ['number' => 1],
            ['name' => '0001', 'description' => 'Punto de venta principal', 'electronic' => true, 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
