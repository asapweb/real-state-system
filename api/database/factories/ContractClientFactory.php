<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Enums\ContractClientRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractClientFactory extends Factory
{
    protected $model = ContractClient::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'client_id' => Client::factory(),
            'role' => ContractClientRole::TENANT,
        ];
    }
}
