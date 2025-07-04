<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalApplicationClientResource extends JsonResource
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
            'rental_application_id' => $this->rental_application_id,
            'client_id' => $this->client_id,
            'role' => $this->role,
            'relationship' => $this->relationship,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'income' => $this->income,
            'currency' => $this->currency,
            'seniority' => $this->seniority,
            'is_property_owner' => $this->is_property_owner,
            'marital_status' => $this->marital_status,
            'spouse_name' => $this->spouse_name,
            'nationality' => $this->nationality,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'rental_application' => new RentalApplicationResource($this->whenLoaded('rentalApplication')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
