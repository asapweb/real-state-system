<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default',
    ];

    protected $casts = [
        'default' => 'boolean',
    ];

    // Relaciones
    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function billingDetails()
    {
        return $this->hasMany(BillingDetail::class);
    }

    // Métodos estáticos
    public static function getDefault()
    {
        return self::where('default', true)->first();
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('default', true);
    }
}
