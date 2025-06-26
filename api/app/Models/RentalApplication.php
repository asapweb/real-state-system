<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\RentalApplicationStatus;
use App\Enums\InsuranceResponsible;
use App\Enums\Currency;

class RentalApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_offer_id',
        'applicant_id',
        'insurance_responsible',
        'insurance_required_from',
        'status',
        'notes',
        'reservation_amount',
        'currency'
    ];

    protected $casts = [
        'insurance_required_from' => 'date',
        'reservation_amount' => 'decimal:2',
        'status' => RentalApplicationStatus::class,
        'insurance_responsible' => InsuranceResponsible::class,
        'currency' => Currency::class,
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalOffer()
    {
        return $this->belongsTo(RentalOffer::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Client::class, 'applicant_id');
    }

    public function rentalApplicationClients()
    {
        return $this->hasMany(RentalApplicationClient::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
