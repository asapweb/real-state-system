<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_account_id',
        'direction',
        'voucher_id',
        'payment_method_id',
        'date',
        'amount',
        'currency',
        'reference',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function isIncome(): bool
    {
        return $this->direction === 'in';
    }

    public function isExpense(): bool
    {
        return $this->direction === 'out';
    }
}
