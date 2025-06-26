<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code_afip',
        'description',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class, 'document_type_id');
    }

    public function taxDocumentClients()
    {
        return $this->hasMany(Client::class, 'tax_document_type_id');
    }
}
