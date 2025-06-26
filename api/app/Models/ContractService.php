<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ServiceType;
use App\Enums\ServicePayer;

class ContractService extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'service_type',
        'account_number',
        'provider_name',
        'owner_name',
        'is_active',
        'has_debt',
        'debt_amount',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_debt' => 'boolean',
        'debt_amount' => 'decimal:2',
        'service_type' => ServiceType::class,
        'paid_by' => ServicePayer::class,
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
