<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'associated_voucher_id',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function associatedVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'associated_voucher_id');
    }
}
