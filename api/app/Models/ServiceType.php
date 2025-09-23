<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contractCharges()
    {
        return $this->hasMany(ContractCharge::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
