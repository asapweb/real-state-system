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
        'base_amount',
        'factor',
        'index_S_date',
        'index_F_date',
        'index_S_value',
        'index_F_value',
        'applied_at',
        'applied_amount',
        'notes',
    ];

    protected $casts = [
        'effective_date'  => 'date',
        'value'           => 'decimal:2',
        'base_amount'     => 'decimal:2',
        'factor'          => 'decimal:8',
        'index_S_date'    => 'date',
        'index_F_date'    => 'date',
        'index_S_value'   => 'decimal:2',
        'index_F_value'   => 'decimal:2',
        'applied_at'      => 'datetime',
        'applied_amount'  => 'decimal:2',
        'type'            => ContractAdjustmentType::class,
    ];

    /**
     * Marca el ajuste como aplicado y guarda la fecha/hora de aplicación.
     */
    public function markAsApplied(?Carbon $timestamp = null): void
    {
        $this->applied_at = $timestamp ?? now();
        $this->save();
    }

    /* ---------------- Relaciones ---------------- */

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
