<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AccountMovement;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'booklet_id',
        'number',
        'issue_date',
        'period',
        'due_date',
        'client_id',
        'contract_id',
        'status',
        'currency',
        'total',
        'notes',
        'meta',
        'cae',
        'cae_expires_at',
        'subtotal_taxed',
        'subtotal_untaxed',
        'subtotal_exempt',
        'subtotal_vat',
        'subtotal',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'period' => 'date',
        'due_date' => 'date',
        'cae_expires_at' => 'date',
        'meta' => 'array',
        'total' => 'decimal:2',
        'subtotal_taxed' => 'decimal:2',
        'subtotal_untaxed' => 'decimal:2',
        'subtotal_exempt' => 'decimal:2',
        'subtotal_vat' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (Voucher $voucher) {
            if (
                $voucher->status === 'issued'
                && $voucher->booklet
                && $voucher->booklet->voucherType?->affects_account
            ) {
                $alreadyExists = AccountMovement::where('voucher_id', $voucher->id)->exists();
                if (! $alreadyExists) {
                    AccountMovement::create([
                        'client_id' => $voucher->client_id,
                        'voucher_id' => $voucher->id,
                        'date' => $voucher->issue_date,
                        'description' => $voucher->booklet->voucherType->name . ' ' . $voucher->number,
                        'amount' => $voucher->booklet->voucherType->credit ? -$voucher->total : $voucher->total,
                        'currency' => $voucher->currency,
                    ]);
                }
            }
        });
    }

    public function items()
    {
        return $this->hasMany(VoucherItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VoucherPayment::class);
    }

    public function applications()
    {
        return $this->hasMany(VoucherApplication::class);
    }

    public function booklet()
    {
        return $this->belongsTo(Booklet::class);
    }
}
