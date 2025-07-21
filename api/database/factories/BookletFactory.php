<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\VoucherType;
use App\Models\SalePoint;

class BookletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Cobranza X',
            'voucher_type_id' => VoucherType::factory(),
            'sale_point_id' => SalePoint::factory(),
            'default_currency' => 'ARS',
            'next_number' => 1,
            'default' => false,
        ];
    }
}
