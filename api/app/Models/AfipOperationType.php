<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfipOperationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'afip_id',
        'is_default',
    ];

    protected $casts = [
        'afip_id' => 'integer',
        'is_default' => 'boolean',
    ];
}
