<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ContractClientRole;

class ContractClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'client_id',
        'role',
        'ownership_percentage',
        'is_primary'
    ];

    protected $casts = [
        'ownership_percentage' => 'decimal:2',
        'is_primary' => 'boolean',
        'role' => ContractClientRole::class,
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
