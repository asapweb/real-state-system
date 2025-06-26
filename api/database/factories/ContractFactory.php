<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\DepositHolder;
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(), // <--- genera una propiedad automáticamente
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addYear(),
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'payment_day' => 10,
            'prorate_first_month' => false,
            'prorate_last_month' => false,
            'commission_type' => 'none',
            'commission_amount' => 0,
            'commission_payer' => 'tenant',
            'is_one_time' => true,
            'insurance_required' => false,
            'insurance_amount' => 0,
            'insurance_company_name' => null,
            'owner_share_percentage' => 100,
            'deposit_amount' => 0,
            'deposit_currency' => 'ARS',
            'deposit_type' => 'none',
            'deposit_holder' => DepositHolder::AGENCY,
            'has_penalty' => false,
            'penalty_type' => 'fixed',
            'penalty_value' => 0,
            'penalty_grace_days' => 0,
            'status' => 'active',
        ];
    }
}
