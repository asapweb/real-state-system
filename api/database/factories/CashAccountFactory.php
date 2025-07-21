<?php

namespace Database\Factories;

use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashAccountFactory extends Factory
{
    protected $model = CashAccount::class;

    public function definition(): array
    {
        $types = ['cash', 'bank', 'virtual'];
        $currencies = ['ARS', 'USD'];
        return [
            'name' => $this->faker->company . ' ' . ucfirst($this->faker->randomElement($types)),
            'type' => $this->faker->randomElement($types),
            'currency' => $this->faker->randomElement($currencies),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
