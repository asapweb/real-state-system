<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code',
        'country_id',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
