<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booklet_id' => 1,
            'number' => $this->faker->unique()->numberBetween(1, 9999),
            'issue_date' => now(),
            'status' => 'issued',
            'currency' => 'ARS',
            'total' => 1210.00,
        ];
    }
}
