<?php

namespace Database\Factories;

use App\Enums\CollectionItemType;
use App\Models\Collection;
use App\Models\CollectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionItemFactory extends Factory
{
    protected $model = CollectionItem::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(CollectionItemType::cases());

        return [
            'collection_id' => Collection::factory(),
            'type' => $type,
            'description' => $this->faker->sentence(),
            'quantity' => 1,
            'unit_price' => $this->faker->randomFloat(2, 1000, 500000),
            'amount' => fn (array $attrs) => $attrs['quantity'] * $attrs['unit_price'],
            'currency' => $this->faker->randomElement(['ARS', 'USD']),
            'meta' => [],
        ];
    }
}
