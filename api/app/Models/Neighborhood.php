<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
