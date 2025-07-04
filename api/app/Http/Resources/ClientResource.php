<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'type' => $this->type,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'document_type_id' => $this->document_type_id,
            'document_number' => $this->document_number,
            'no_document' => $this->no_document,
            'tax_document_type_id' => $this->tax_document_type_id,
            'tax_document_number' => $this->tax_document_number,
            'tax_condition_id' => $this->tax_condition_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'civil_status_id' => $this->civil_status_id,
            'nationality_id' => $this->nationality_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),
            'tax_document_type' => new DocumentTypeResource($this->whenLoaded('taxDocumentType')),
            'tax_condition' => new TaxConditionResource($this->whenLoaded('taxCondition')),
            'civil_status' => new CivilStatusResource($this->whenLoaded('civilStatus')),
            'nationality' => new CountryResource($this->whenLoaded('nationality')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
