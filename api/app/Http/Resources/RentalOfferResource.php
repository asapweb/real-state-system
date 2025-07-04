<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalOfferResource extends JsonResource
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
            'price' => $this->price,
            'currency' => $this->currency,
            'duration_months' => $this->duration_months,
            'availability_date' => $this->availability_date,
            'common_expenses_amount' => $this->common_expenses_amount,
            'seal_required' => $this->seal_required,
            'seal_amount' => $this->seal_amount,
            'seal_currency' => $this->seal_currency,
            'seal_percentage_owner' => $this->seal_percentage_owner,
            'seal_percentage_tenant' => $this->seal_percentage_tenant,
            'includes_insurance' => $this->includes_insurance,
            'insurance_quote_amount' => $this->insurance_quote_amount,
            'commission_policy' => $this->commission_policy,
            'deposit_policy' => $this->deposit_policy,
            'allow_pets' => $this->allow_pets,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'property' => new PropertyResource($this->whenLoaded('property')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'rental_applications' => RentalApplicationResource::collection($this->whenLoaded('rentalApplications')),
            'service_statuses' => RentalOfferServiceStatusResource::collection($this->whenLoaded('serviceStatuses')),
        ];
    }
}
