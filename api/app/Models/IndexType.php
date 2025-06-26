<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contractAdjustments()
    {
        return $this->hasMany(ContractAdjustment::class);
    }
}
