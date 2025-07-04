<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate', 'is_default', 'included_in_vat_detail'];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'included_in_vat_detail' => 'boolean',
    ];
}
