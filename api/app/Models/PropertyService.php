<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ServiceType;

class PropertyService extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'service_type',
        'account_number',
        'provider_name',
        'owner_name',
        'is_active',
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'is_active' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalOfferServiceStatuses()
    {
        return $this->hasMany(RentalOfferServiceStatus::class);
    }
}
