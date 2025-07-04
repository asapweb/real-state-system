<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
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
            'property_type_id' => $this->property_type_id,
            'street' => $this->street,
            'number' => $this->number,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'postal_code' => $this->postal_code,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'neighborhood_id' => $this->neighborhood_id,
            'tax_code' => $this->tax_code,
            'cadastral_reference' => $this->cadastral_reference,
            'registry_number' => $this->registry_number,
            'has_parking' => $this->has_parking,
            'parking_details' => $this->parking_details,
            'allows_pets' => $this->allows_pets,
            'iva_condition' => $this->iva_condition,
            'status' => $this->status,
            'observations' => $this->observations,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'property_type' => new PropertyTypeResource($this->whenLoaded('propertyType')),
            'owners' => PropertyOwnerResource::collection($this->whenLoaded('owners')),
            'services' => PropertyServiceResource::collection($this->whenLoaded('services')),
            'country' => new CountryResource($this->whenLoaded('country')),
            'state' => new StateResource($this->whenLoaded('state')),
            'city' => new CityResource($this->whenLoaded('city')),
            'neighborhood' => new NeighborhoodResource($this->whenLoaded('neighborhood')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
