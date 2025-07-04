<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = ['Efectivo', 'Transferencia', 'Mercado Pago', 'Cheque', 'Tarjeta'];

        foreach ($methods as $i => $name) {
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $name],
                ['code' => strtolower(str_replace(' ', '_', $name)), 'is_default' => $i === 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
