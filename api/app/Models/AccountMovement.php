<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'voucher_id',
        'date',
        'description',
        'amount',
        'currency',
        'is_initial',
        'meta',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'date' => 'date',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
