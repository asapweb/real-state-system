<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashAccount;

class CashAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas de caja realistas
        $general = CashAccount::create([
            'name' => 'Caja General',
            'type' => 'cash',
            'currency' => 'ARS',
            'is_active' => true,
        ]);
        $galicia = CashAccount::create([
            'name' => 'Banco Galicia - Cta Cte',
            'type' => 'bank',
            'currency' => 'ARS',
            'is_active' => true,
        ]);
        $mp = CashAccount::create([
            'name' => 'Mercado Pago',
            'type' => 'virtual',
            'currency' => 'ARS',
            'is_active' => true,
        ]);
        $nacion_usd = CashAccount::create([
            'name' => 'Banco Nación USD',
            'type' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Guardar IDs en config temporal para otros seeders
        config(['seed.cash_accounts.general' => $general->id]);
        config(['seed.cash_accounts.galicia' => $galicia->id]);
        config(['seed.cash_accounts.mp' => $mp->id]);
        config(['seed.cash_accounts.nacion_usd' => $nacion_usd->id]);
    }
}
