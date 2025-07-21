<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'letter',
        'afip_id',
        'credit',
        'affects_account',
        'affects_cash',
        'order',
    ];

    protected $casts = [
        'credit' => 'boolean',
        'affects_account' => 'boolean',
        'affects_cash' => 'boolean',
    ];

    // --- Relaciones ---

    public function booklets()
    {
        return $this->hasMany(Booklet::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    // --- Helpers ---

    public function fullCode(): string
    {
        return "{$this->short_name}-{$this->letter}";
    }
}
