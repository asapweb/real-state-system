<?php

namespace Database\Factories;

use App\Models\VoucherPayment;
use App\Models\Voucher;
use App\Models\PaymentMethod;
use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherPaymentFactory extends Factory
{
    protected $model = VoucherPayment::class;

    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'cash_account_id' => CashAccount::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'reference' => $this->faker->optional()->bothify('REF-####-????'),
        ];
    }
}
