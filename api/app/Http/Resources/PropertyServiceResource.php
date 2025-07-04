<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyServiceResource extends JsonResource
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
            'property_id' => $this->property_id,
            'service_type' => $this->service_type,
            'account_number' => $this->account_number,
            'provider_name' => $this->provider_name,
            'owner_name' => $this->owner_name,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'property' => new PropertyResource($this->whenLoaded('property')),
            'rental_offer_service_statuses' => RentalOfferServiceStatusResource::collection($this->whenLoaded('rentalOfferServiceStatuses')),
        ];
    }
}
