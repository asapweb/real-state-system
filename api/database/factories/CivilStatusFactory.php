<?php

namespace Database\Factories;

use App\Models\CivilStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CivilStatusFactory extends Factory
{
    protected $model = CivilStatus::class;

    public function definition(): array
    {
        return [
            'name' => 'Soltero',
            'is_default' => true,
        ];
    }
}
