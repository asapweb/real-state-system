<?php

namespace App\Models;

use App\Enums\CalculationMode;
use App\Enums\IndexFrequency;
use Illuminate\Database\Eloquent\Model;

class IndexType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'calculation_mode',
        'frequency',
        'is_cumulative',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_cumulative' => 'boolean',
        'calculation_mode' => CalculationMode::class,
        'frequency' => IndexFrequency::class,
    ];

    public function isCumulative(): bool
    {
        return $this->is_cumulative;
    }


    public function contractAdjustments()
    {
        return $this->hasMany(ContractAdjustment::class);
    }

    public function values()
    {
        return $this->hasMany(IndexValue::class);
    }

    /**
     * Get the values for ratio calculation mode
     */
    public function ratioValues()
    {
        return $this->hasMany(IndexValue::class)->whereNotNull('effective_date');
    }

    /**
     * Check if this index type uses daily frequency
     */
    public function isDailyFrequency(): bool
    {
        return $this->frequency === IndexFrequency::DAILY;
    }

    /**
     * Check if this index type uses monthly frequency
     */
    public function isMonthlyFrequency(): bool
    {
        return $this->frequency === IndexFrequency::MONTHLY;
    }

    /**
     * Check if this index type uses multiplicative_chain calculation mode
     */
    public function isMultiplicativeChainMode(): bool
    {
        return $this->calculation_mode === CalculationMode::MULTIPLICATIVE_CHAIN;
    }
}
