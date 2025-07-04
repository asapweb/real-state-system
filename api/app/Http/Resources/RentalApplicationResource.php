<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalApplicationResource extends JsonResource
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
            'rental_offer_id' => $this->rental_offer_id,
            'applicant_id' => $this->applicant_id,
            'insurance_responsible' => $this->insurance_responsible,
            'insurance_required_from' => $this->insurance_required_from,
            'status' => $this->status,
            'notes' => $this->notes,
            'reservation_amount' => $this->reservation_amount,
            'currency' => $this->currency,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'property' => new PropertyResource($this->whenLoaded('property')),
            'rental_offer' => new RentalOfferResource($this->whenLoaded('rentalOffer')),
            'applicant' => new ClientResource($this->whenLoaded('applicant')),
            'rental_application_clients' => RentalApplicationClientResource::collection($this->whenLoaded('rentalApplicationClients')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
