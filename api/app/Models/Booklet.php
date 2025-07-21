<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booklet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'voucher_type_id',
        'sale_point_id',
        'default_currency',
        'next_number',
        'default',
    ];

    protected $casts = [
        'default' => 'boolean',
    ];

    // --- Relaciones ---

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function salePoint()
    {
        return $this->belongsTo(SalePoint::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    // --- Accessors ---

    public function getFormattedSalePointNumber(): ?string
    {
        return $this->salePoint
            ? str_pad($this->salePoint->number, 4, '0', STR_PAD_LEFT)
            : null;
    }

    // --- Methods ---

    /**
     * Generate the next voucher number for this booklet
     * and update the next_number field
     */
    public function generateNextNumber(): string
    {
        $nextNumber = $this->next_number ?? 1;

        // Update the next_number for future vouchers
        $this->update(['next_number' => $nextNumber + 1]);

        return str_pad($nextNumber, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get the formatted voucher number with sale point and booklet info
     */
    public function getFormattedVoucherNumber(string $number): string
    {
        $salePointNumber = $this->getFormattedSalePointNumber();
        $voucherType = $this->voucherType;

        if ($salePointNumber && $voucherType) {
            return "{$voucherType->short_name} {$voucherType->letter} {$salePointNumber}-{$number}";
        }

        return $number;
    }
}
