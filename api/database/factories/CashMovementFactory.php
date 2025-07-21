<?php

namespace Database\Factories;

use App\Models\CashMovement;
use App\Models\CashAccount;
use App\Models\Voucher;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        $direction = $this->faker->randomElement(['in', 'out']);
        $amount = $this->faker->randomFloat(2, 1000, 100000);
        $cashAccount = CashAccount::inRandomOrder()->first();
        $paymentMethod = PaymentMethod::inRandomOrder()->first();
        $voucher = $this->faker->boolean(40) ? Voucher::factory() : null;
        $concepts = [
            'Cobro alquiler', 'Cobro expensas', 'Ingreso extra', 'Ajuste saldo',
            'Pago proveedor', 'Retiro de caja', 'Pago impuestos', 'Ajuste manual'
        ];
        return [
            'cash_account_id' => $cashAccount ? $cashAccount->id : CashAccount::factory(),
            'voucher_id' => $voucher,
            'payment_method_id' => $this->faker->boolean(70) && $paymentMethod ? $paymentMethod->id : null,
            'date' => now()->subDays(rand(0, 30)),
            'amount' => $amount,
            'direction' => $direction,
            'reference' => $this->faker->optional()->bothify('REF-####-????'),
            'meta' => [
                'user' => $this->faker->randomElement(['Admin', 'Cajero', 'Contador']),
                'concept' => $this->faker->randomElement($concepts),
            ],
        ];
    }
}
