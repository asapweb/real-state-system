<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachment_category_id',
        'name',
        'file_path',
        'mime_type',
        'size',
        'uploaded_by',
        'attachable_id',
        'attachable_type'
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = [
        'file_url',
    ];

    public function category()
    {
        return $this->belongsTo(AttachmentCategory::class, 'attachment_category_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $this->file_path,
            now()->addMinutes(5)
        );

    }

}
