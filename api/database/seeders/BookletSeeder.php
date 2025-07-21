<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherType;
use App\Models\SalePoint;
use App\Models\Booklet;

class BookletSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Factura A', 'short_name' => 'FAC', 'letter' => 'A'],
            ['name' => 'Nota de Débito A', 'short_name' => 'N/D', 'letter' => 'A'],
            ['name' => 'Nota de Crédito A', 'short_name' => 'N/C', 'letter' => 'A'],

            ['name' => 'Factura B', 'short_name' => 'FAC', 'letter' => 'B'],
            ['name' => 'Nota de Débito B', 'short_name' => 'N/D', 'letter' => 'B'],
            ['name' => 'Nota de Crédito B', 'short_name' => 'N/C', 'letter' => 'B'],

            ['name' => 'Factura C', 'short_name' => 'FAC', 'letter' => 'C'],
            ['name' => 'Nota de Débito C', 'short_name' => 'N/D', 'letter' => 'C'],
            ['name' => 'Nota de Crédito C', 'short_name' => 'N/C', 'letter' => 'C'],

            ['name' => 'Recibo X (Cobranza)', 'short_name' => 'RCB', 'letter' => 'X'],
            ['name' => 'Recibo X (Pago)', 'short_name' => 'RPG', 'letter' => 'X'],
            ['name' => 'Liquidación X', 'short_name' => 'LIQ', 'letter' => 'X'],
            ['name' => 'Nota de Crédito X', 'short_name' => 'N/C', 'letter' => 'X'],
            ['name' => 'Nota de Débito X', 'short_name' => 'N/D', 'letter' => 'X'],
            ['name' => 'Cobranza X', 'short_name' => 'COB', 'letter' => 'X'],
        ];

        $salePoint = SalePoint::firstOrCreate(
            ['number' => 22],
            ['name' => 'Punto de Venta 22', 'description' => 'PDV interno', 'electronic' => false]
        );

        foreach ($types as $type) {
            $voucherType = VoucherType::where('name', $type['name'])
                ->where('letter', $type['letter'])
                ->first();

            if ($voucherType) {
                Booklet::firstOrCreate([
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
}
