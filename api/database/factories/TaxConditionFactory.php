<?php

namespace Database\Factories;

use App\Models\TaxCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxConditionFactory extends Factory
{
    protected $model = TaxCondition::class;

    public function definition(): array
    {
        return [
            'name' => 'Consumidor Final',
            'is_default' => true,
        ];
    }
}
