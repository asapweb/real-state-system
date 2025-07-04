<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'applied_to_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function appliedTo()
    {
        return $this->belongsTo(Voucher::class, 'applied_to_id');
    }
}
