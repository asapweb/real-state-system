<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => 1,
            'applied_to_id' => 2,
            'amount' => 1000.00,
        ];
    }
}
