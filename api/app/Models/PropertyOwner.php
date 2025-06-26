<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'client_id',
        'ownership_percentage'
    ];

    protected $casts = [
        'ownership_percentage' => 'decimal:2',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
