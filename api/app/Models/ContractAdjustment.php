<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ContractAdjustmentType;
use Carbon\Carbon;

class ContractAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'effective_date',
        'type',
        'index_type_id',
        'value',
        'applied_at',
        'notes'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'value' => 'decimal:2',
        'applied_at' => 'datetime',
        'type' => ContractAdjustmentType::class,
    ];

    public function markAsApplied(?Carbon $timestamp = null): void
    {
        $this->applied_at = $timestamp ?? now();
        $this->save();
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function indexType()
    {
        return $this->belongsTo(IndexType::class);
    }

}
