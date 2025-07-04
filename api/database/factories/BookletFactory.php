<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Cobranza X',
            'prefix' => 'COB',
            'voucher_type_id' => 1,
            'sale_point_id' => 1,
            'default_currency' => 'ARS',
            'next_number' => 1,
        ];
    }
}
