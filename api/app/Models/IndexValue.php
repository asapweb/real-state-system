<?php

namespace App\Models;

use App\Enums\CalculationMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'index_type_id',
        'effective_date',
        'value',
        'percentage',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'value' => 'decimal:4',
        'percentage' => 'decimal:2',
    ];

    public function indexType()
    {
        return $this->belongsTo(IndexType::class);
    }

    public function getCalculationModeAttribute()
    {
        return $this->indexType?->calculation_mode;
    }
}
