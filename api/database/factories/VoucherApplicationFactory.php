<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Voucher;
use App\Models\VoucherApplication;

class VoucherApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'applied_to_id' => Voucher::factory(),
            'amount' => 1000.00,
        ];
    }

    public function from(Voucher $origin): static
    {
        return $this->state(fn () => ['voucher_id' => $origin->id]);
    }

    public function to(Voucher $target): static
    {
        return $this->state(fn () => ['applied_to_id' => $target->id]);
    }
}
