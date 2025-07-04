<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_method_id' => 1,
            'amount' => 1210.00,
            'reference' => 'REF-001',
        ];
    }
}
