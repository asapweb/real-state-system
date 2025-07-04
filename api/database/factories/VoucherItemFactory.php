<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'rent',
            'description' => 'Alquiler mensual',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'subtotal' => 1000.00,
            'vat_amount' => 210.00,
            'subtotal_with_vat' => 1210.00,
            'tax_rate_id' => 1,
        ];
    }
}
