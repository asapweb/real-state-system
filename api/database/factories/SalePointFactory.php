<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalePointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => '0001',
            'number' => 1,
            'description' => 'Punto de venta principal',
            'electronic' => true,
        ];
    }
}
