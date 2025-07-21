<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use App\Models\CashAccount;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener cuentas de caja creadas por CashAccountSeeder
        $general = CashAccount::where('name', 'Caja General')->first();
        $galicia = CashAccount::where('name', 'Banco Galicia - Cta Cte')->first();
        $mp = CashAccount::where('name', 'Mercado Pago')->first();
        $nacion_usd = CashAccount::where('name', 'Banco Nación USD')->first();

        PaymentMethod::create([
            'name' => 'Efectivo',
            'requires_reference' => false,
            'default_cash_account_id' => $general?->id,
            'is_default' => true,
            'handled_by_agency' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Transferencia',
            'requires_reference' => true,
            'default_cash_account_id' => $galicia?->id,
            'is_default' => false,
            'handled_by_agency' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Cheque',
            'requires_reference' => true,
            'default_cash_account_id' => $nacion_usd?->id,
            'is_default' => false,
            'handled_by_agency' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Mercado Pago',
            'requires_reference' => true,
            'default_cash_account_id' => $mp?->id,
            'is_default' => false,
            'handled_by_agency' => true,
        ]);

        PaymentMethod::create([
            'name' => 'Saldo a favor',
            'requires_reference' => false,
            'default_cash_account_id' => null,
            'is_default' => false,
            'handled_by_agency' => false,
        ]);

        PaymentMethod::create([
            'name' => 'Transferencia directa al propietario',
            'requires_reference' => true,
            'default_cash_account_id' => null,
            'is_default' => false,
            'handled_by_agency' => false, // <- clave para evitar movimientos de caja
        ]);
    }
}
