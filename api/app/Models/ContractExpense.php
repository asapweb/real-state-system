<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'service_type',
        'amount',
        'currency',
        'period',
        'due_date',
        'paid_by',
        'is_paid',
        'paid_at',
        'description',
        'included_in_collection',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period' => 'string',
        'due_date' => 'date',
        'paid_at' => 'date',
        'is_paid' => 'boolean',
        'included_in_collection' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
