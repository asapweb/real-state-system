<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\RentalOfferStatus;
use App\Enums\Currency;

class RentalOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'price',
        'currency',
        'duration_months',
        'availability_date',
        'common_expenses_amount',
        'seal_required',
        'seal_amount',
        'seal_currency',
        'seal_percentage_owner',
        'seal_percentage_tenant',
        'includes_insurance',
        'insurance_quote_amount',
        'commission_policy',
        'deposit_policy',
        'allow_pets',
        'status',
        'published_at',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'common_expenses_amount' => 'decimal:2',
        'seal_required' => 'boolean',
        'seal_amount' => 'decimal:2',
        'seal_percentage_owner' => 'decimal:2',
        'seal_percentage_tenant' => 'decimal:2',
        'includes_insurance' => 'boolean',
        'insurance_quote_amount' => 'decimal:2',
        'allow_pets' => 'boolean',
        'availability_date' => 'date',
        'published_at' => 'datetime',
        'status' => RentalOfferStatus::class,
        'currency' => Currency::class,
        'seal_currency' => Currency::class,
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function rentalApplications()
    {
        return $this->hasMany(RentalApplication::class);
    }

    public function serviceStatuses()
    {
        return $this->hasMany(RentalOfferServiceStatus::class);
    }
}
