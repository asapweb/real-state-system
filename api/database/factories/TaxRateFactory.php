<?php

namespace Database\Factories;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'name' => '21%',
            'rate' => 21.00,
            'is_default' => true,
            'included_in_vat_detail' => true,
        ];
    }
}
