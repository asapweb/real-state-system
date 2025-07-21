<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'number',
        'electronic',
    ];

    protected $casts = [
        'electronic' => 'boolean',
        'number' => 'integer',
    ];

    // --- Relaciones ---

    public function booklets()
    {
        return $this->hasMany(Booklet::class);
    }
}
