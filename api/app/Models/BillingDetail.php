<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'tax_condition_id',
        'document_type_id',
        'document_number',
        'billing_name',
        'billing_address',
        'is_default',
    ];

    // Definición de los casts para asegurar que ciertos atributos se manejen como tipos específicos
    protected $casts = [
        'is_default' => 'boolean', // Asegura que 'is_default' sea tratado como booleano
    ];

    // Relaciones con otros modelos
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function taxCondition()
    {
        return $this->belongsTo(TaxCondition::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
