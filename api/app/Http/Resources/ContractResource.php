<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ContractAdjustmentResource;

class ContractResource extends JsonResource
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
            'rental_application_id' => $this->rental_application_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'monthly_amount' => $this->monthly_amount,
            'currency' => $this->currency,
            'payment_day' => $this->payment_day,
            'prorate_first_month' => $this->prorate_first_month,
            'prorate_last_month' => $this->prorate_last_month,
            'commission_type' => $this->commission_type,
            'commission_amount' => $this->commission_amount,
            'commission_payer' => $this->commission_payer,
            'is_one_time' => $this->is_one_time,
            'insurance_required' => $this->insurance_required,
            'insurance_amount' => $this->insurance_amount,
            'insurance_company_name' => $this->insurance_company_name,
            'owner_share_percentage' => $this->owner_share_percentage,
            'deposit_amount' => $this->deposit_amount,
            'deposit_currency' => $this->deposit_currency,
            'deposit_type' => $this->deposit_type,
            'deposit_holder' => $this->deposit_holder,
            'has_penalty' => $this->has_penalty,
            'penalty_type' => $this->penalty_type,
            'penalty_value' => $this->penalty_value,
            'penalty_grace_days' => $this->penalty_grace_days,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'collection_booklet' => new BookletResource($this->collectionBooklet),
            'settlement_booklet' => new BookletResource($this->settlementBooklet),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'rental_application' => new RentalApplicationResource($this->whenLoaded('rentalApplication')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'adjustments' => ContractAdjustmentResource::collection($this->whenLoaded('adjustments')),
            'clients' => ContractClientResource::collection($this->whenLoaded('clients')),
            'owners' => ContractClientResource::collection($this->whenLoaded('owners')),
            'main_tenant' => new ContractClientResource($this->whenLoaded('mainTenant')),
            'services' => ContractServiceResource::collection($this->whenLoaded('services')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),
            'expenses' => ContractExpenseResource::collection($this->whenLoaded('expenses')),
        ];
    }
}
