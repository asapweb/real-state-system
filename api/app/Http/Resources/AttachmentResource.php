<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attachment_category_id' => $this->attachment_category_id,
            'name' => $this->name,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploaded_by' => $this->uploaded_by,
            'attachable_id' => $this->attachable_id,
            'attachable_type' => $this->attachable_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'category' => new AttachmentCategoryResource($this->whenLoaded('category')),
            'uploaded_by_user' => new UserResource($this->whenLoaded('uploadedBy')),
        ];
    }
}
