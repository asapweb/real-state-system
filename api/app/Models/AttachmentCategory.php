<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'context',
        'is_required',
        'is_default',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
