<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Comprobantes legales A
            ['name' => 'Factura A', 'short_name' => 'FAC', 'letter' => 'A', 'afip_id' => 1, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito A', 'short_name' => 'N/D', 'letter' => 'A', 'afip_id' => 2, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito A', 'short_name' => 'N/C', 'letter' => 'A', 'afip_id' => 3, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],

            // Comprobantes legales B
            ['name' => 'Factura B', 'short_name' => 'FAC', 'letter' => 'B', 'afip_id' => 6, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito B', 'short_name' => 'N/D', 'letter' => 'B', 'afip_id' => 7, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito B', 'short_name' => 'N/C', 'letter' => 'B', 'afip_id' => 8, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],

            // Comprobantes legales C
            ['name' => 'Factura C', 'short_name' => 'FAC', 'letter' => 'C', 'afip_id' => 11, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito C', 'short_name' => 'N/D', 'letter' => 'C', 'afip_id' => 12, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito C', 'short_name' => 'N/C', 'letter' => 'C', 'afip_id' => 13, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],

            // Comprobantes internos
            ['name' => 'Recibo X (Cobranza)', 'short_name' => 'RCB', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => true],
            ['name' => 'Recibo X (Pago)', 'short_name' => 'RPG', 'letter' => 'X', 'afip_id' => null, 'credit' => true, 'affects_account' => true, 'affects_cash' => true],
            ['name' => 'Liquidación X', 'short_name' => 'LIQ', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito X', 'short_name' => 'N/C', 'letter' => 'X', 'afip_id' => null, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito X', 'short_name' => 'N/D', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],

            ['name' => 'Cobranza X', 'short_name' => 'COB', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Liquidación X', 'short_name' => 'LIQ', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
        ];

        foreach ($types as $i => $data) {
            DB::table('voucher_types')->updateOrInsert(
                ['name' => $data['name']],
                array_merge($data, ['order' => $i + 1, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
