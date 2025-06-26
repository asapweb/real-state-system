<?php

namespace App\Models;

use App\Enums\CollectionItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'type' => CollectionItemType::class,
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
