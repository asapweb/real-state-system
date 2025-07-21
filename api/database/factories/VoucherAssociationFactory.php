<?php

namespace Database\Factories;

use App\Models\Voucher;
use App\Models\VoucherAssociation;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherAssociationFactory extends Factory
{
    protected $model = VoucherAssociation::class;

    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'associated_voucher_id' => Voucher::factory(),
        ];
    }
}
