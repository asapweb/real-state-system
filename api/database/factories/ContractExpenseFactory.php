<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractExpenseFactory extends Factory
{
    protected $model = ContractExpense::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'service_type' => $this->faker->randomElement(['expensas', 'agua', 'gas']),
            'amount' => $this->faker->randomFloat(2, 1000, 10000),
            'currency' => $this->faker->randomElement(['ARS', 'USD']),
            'paid_by' => 'agency',
            'is_paid' => true,
            'included_in_collection' => false,
            'period' => now()->format('Y-m-d'),
        ];
    }
}
