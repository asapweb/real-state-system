<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\RentalApplicationClientRole;

class RentalApplicationClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_application_id',
        'client_id',
        'role',
        'relationship',
        'occupation',
        'employer',
        'income',
        'currency',
        'seniority',
        'is_property_owner',
        'marital_status',
        'spouse_name',
        'nationality',
    ];

    protected $casts = [
        'role' => RentalApplicationClientRole::class,
        'income' => 'decimal:2',
        'currency' => \App\Enums\Currency::class,
        'is_property_owner' => 'boolean',
    ];

    public function rentalApplication()
    {
        return $this->belongsTo(RentalApplication::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
