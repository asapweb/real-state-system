<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Cobranza X',
            'short_name' => 'COB',
            'letter' => 'X',
            'afip_id' => null,
            'credit' => false,
            'affects_account' => true,
            'affects_cash' => false,
        ];
    }
}
