<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PropertyStatus;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_type_id',
        'street',
        'number',
        'floor',
        'apartment',
        'postal_code',
        'country_id',
        'state_id',
        'city_id',
        'neighborhood_id',
        'tax_code',
        'cadastral_reference',
        'registry_number',
        'has_parking',
        'parking_details',
        'allows_pets',
        'iva_condition',
        'status',
        'observations',
        'created_by'
    ];

    protected $casts = [
        'has_parking' => 'boolean',
        'allows_pets' => 'boolean',
        'status' => PropertyStatus::class,
    ];

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function owners()
    {
        return $this->hasMany(PropertyOwner::class);
    }

    public function services()
    {
        return $this->hasMany(PropertyService::class);
    }


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
