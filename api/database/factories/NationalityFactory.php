<?php

namespace Database\Factories;

use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;

class NationalityFactory extends Factory
{
    protected $model = Nationality::class;

    public function definition(): array
    {
        return [
            'name' => 'Argentina',
            'is_default' => true,
        ];
    }
}
