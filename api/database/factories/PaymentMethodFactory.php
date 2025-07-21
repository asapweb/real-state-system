<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word . ' ' . $this->faker->randomElement(['Efectivo', 'Transferencia', 'Cheque', 'MercadoPago']),
            'requires_reference' => $this->faker->boolean(30),
            'default_cash_account_id' => CashAccount::factory(),
            'code' => $this->faker->unique()->lexify('PMT-????'),
            'is_default' => $this->faker->boolean(10),
            'handled_by_agency' => $this->faker->boolean(80),
        ];
    }
}
