<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ServicePayer;

class RentalOfferServiceStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_offer_id',
        'property_service_id',
        'is_active',
        'has_debt',
        'debt_amount',
        'paid_by',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_debt' => 'boolean',
        'debt_amount' => 'decimal:2',
        'paid_by' => ServicePayer::class,
    ];

    public function rentalOffer()
    {
        return $this->belongsTo(RentalOffer::class);
    }

    public function propertyService()
    {
        return $this->belongsTo(PropertyService::class);
    }
}
