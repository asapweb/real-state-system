<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'requires_reference',
        'default_cash_account_id',
        'code',
        'is_default',
        'handled_by_agency',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_default' => 'boolean',
        'handled_by_agency' => 'boolean',
    ];

    public function defaultCashAccount()
    {
        return $this->belongsTo(CashAccount::class, 'default_cash_account_id');
    }

    public function voucherPayments()
    {
        return $this->hasMany(VoucherPayment::class);
    }
}
