<?php

namespace Database\Factories;

use App\Models\ContractAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractAdjustmentFactory extends Factory
{
    protected $model = ContractAdjustment::class;

    public function definition(): array
    {
        return [
            'contract_id' => \App\Models\Contract::factory(),
            'effective_date' => '2025-07-01',
            'type' => 'percentage',
            'value' => 10,
        ];
    }
}
