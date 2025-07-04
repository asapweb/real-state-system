<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CashMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => 1,
            'payment_method_id' => 1,
            'date' => now(),
            'amount' => 1000.00,
            'currency' => 'ARS',
            'reference' => 'TRANS-1234',
        ];
    }
}
