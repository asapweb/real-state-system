<?php

namespace App\Models;

use App\Enums\VoucherItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'vat_amount',
        'tax_rate_id',
        'subtotal_with_vat',
        'meta',
    ];

    protected $casts = [
        'type' => VoucherItemType::class,
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'subtotal_with_vat' => 'decimal:2',
        'meta' => 'array',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
}
