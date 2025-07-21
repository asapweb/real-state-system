<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashAccount;
use App\Models\CashMovement;

class CashMovementSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = CashAccount::where('is_active', true)->get();
        foreach ($accounts as $account) {
            CashMovement::factory()
                ->count(10)
                ->state(['cash_account_id' => $account->id])
                ->create();
        }
    }
}
