<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
