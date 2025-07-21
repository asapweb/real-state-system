<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'default_cash_account_id');
    }

    public function voucherPayments()
    {
        return $this->hasMany(VoucherPayment::class);
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function getBalanceAttribute()
    {
        return $this->cashMovements->sum(function ($movement) {
            return $movement->isIncome() ? $movement->amount : -$movement->amount;
        });
    }
}
