<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherType;
use App\Models\SalePoint;
use App\Models\Booklet;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $voucherTypes = [
            ['name' => 'Factura A', 'short_name' => 'FAC', 'letter' => 'A', 'afip_id' => 1, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito A', 'short_name' => 'N/D', 'letter' => 'A', 'afip_id' => 2, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito A', 'short_name' => 'N/C', 'letter' => 'A', 'afip_id' => 3, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Factura B', 'short_name' => 'FAC', 'letter' => 'B', 'afip_id' => 6, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito B', 'short_name' => 'N/D', 'letter' => 'B', 'afip_id' => 7, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito B', 'short_name' => 'N/C', 'letter' => 'B', 'afip_id' => 8, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Factura C', 'short_name' => 'FAC', 'letter' => 'C', 'afip_id' => 11, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito C', 'short_name' => 'N/D', 'letter' => 'C', 'afip_id' => 12, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito C', 'short_name' => 'N/C', 'letter' => 'C', 'afip_id' => 13, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Factura X', 'short_name' => 'FAC', 'letter' => 'X', 'afip_id' => 1, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Recibo X (Cobranza)', 'short_name' => 'RCB', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => true],
            ['name' => 'Recibo X (Pago)', 'short_name' => 'RPG', 'letter' => 'X', 'afip_id' => null, 'credit' => true, 'affects_account' => true, 'affects_cash' => true],
            ['name' => 'Liquidación X', 'short_name' => 'LIQ', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Crédito X', 'short_name' => 'N/C', 'letter' => 'X', 'afip_id' => null, 'credit' => true, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Nota de Débito X', 'short_name' => 'N/D', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
            ['name' => 'Cobranza X', 'short_name' => 'COB', 'letter' => 'X', 'afip_id' => null, 'credit' => false, 'affects_account' => true, 'affects_cash' => false],
        ];

        foreach ($voucherTypes as $type) {
            $voucherType = VoucherType::firstOrCreate([
                'name' => $type['name'],
                'letter' => $type['letter'],
            ], $type);

            $salePoint = SalePoint::firstOrCreate(
                ['number' => 22],
                ['name' => 'Punto de Venta 22', 'description' => 'PDV interno', 'electronic' => false]
            );

            Booklet::firstOrCreate([
                'prefix' => $type['short_name'] . '-' . $type['letter'],
                'voucher_type_id' => $voucherType->id,
                'sale_point_id' => $salePoint->id,
            ], [
                'name' => $type['name'],
                'default_currency' => 'ARS',
                'next_number' => 1,
            ]);
        }
    }
}