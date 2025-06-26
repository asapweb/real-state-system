<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'contract_id' => \App\Models\Contract::factory(),
            'status' => 'pending',
            'currency' => 'ARS',
            'issue_date' => now()->subMonth(),
            'due_date' => now()->subDays(10),
            'period' => now()->subMonth()->format('Y-m'),
            'total_amount' => 100000,
        ];
    }
}
