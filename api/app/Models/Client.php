<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'last_name',
        'document_type_id',
        'document_number',
        'no_document',
        'tax_document_type_id',
        'tax_document_number',
        'tax_condition_id',
        'email',
        'phone',
        'address',
        'civil_status_id',
        'nationality_id',
        'notes',
    ];

    protected $appends = ['full_name'];

    protected $casts = [
        'no_document' => 'boolean',
    ];

    /*
     |--------------------------------------------------------------------------
     | Accessors
     |--------------------------------------------------------------------------
     */

    // Define the full_name accessor
    public function getFullNameAttribute()
    {
        return trim("{$this->name} {$this->last_name}");
    }

    /*
     |--------------------------------------------------------------------------
     | Mutators
     |--------------------------------------------------------------------------
     */

    public function setNoDocumentAttribute($value)
    {
        $this->attributes['no_document'] = $value;

        if ($value) {
            $this->attributes['document_number'] = null;
            $this->attributes['document_type_id'] = null;
        }
    }

    protected static function booted()
    {
        static::saving(function ($client) {
            if ($client->no_document) {
                $client->document_number = null;
                $client->document_type_id = null;
            }
        });
    }

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function taxDocumentType()
    {
        return $this->belongsTo(DocumentType::class, 'tax_document_type_id');
    }

    public function taxCondition()
    {
        return $this->belongsTo(TaxCondition::class);
    }

    public function civilStatus()
    {
        return $this->belongsTo(CivilStatus::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Country::class, 'nationality_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function accountMovements()
    {
        return $this->hasMany(AccountMovement::class);
    }
}
