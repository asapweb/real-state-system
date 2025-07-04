<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyOwnerResource extends JsonResource
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
            'client_id' => $this->client_id,
            'ownership_percentage' => $this->ownership_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'property' => new PropertyResource($this->whenLoaded('property')),
            'client' => new ClientResource($this->whenLoaded('client')),
        ];
    }
}
