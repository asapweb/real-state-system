<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'status',
        'currency',
        'issue_date',
        'due_date',
        'period',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(CollectionItem::class);
    }

    // public function receipts()
    // {
    //     return $this->hasMany(CollectionReceipt::class);
    // }

    // public function adjustments()
    // {
    //     return $this->hasMany(CollectionAdjustment::class);
    // }
}
