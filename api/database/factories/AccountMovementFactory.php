<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => 1,
            'voucher_id' => null,
            'date' => now(),
            'description' => 'Saldo inicial',
            'amount' => 1000.00,
            'currency' => 'ARS',
        ];
    }
}
