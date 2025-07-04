<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractServiceResource extends JsonResource
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
            'contract_id' => $this->contract_id,
            'service_type' => $this->service_type,
            'account_number' => $this->account_number,
            'provider_name' => $this->provider_name,
            'owner_name' => $this->owner_name,
            'is_active' => $this->is_active,
            'has_debt' => $this->has_debt,
            'debt_amount' => $this->debt_amount,
            'paid_by' => $this->paid_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'contract' => $this->whenLoaded('contract', function () {
                return [
                    'id' => $this->contract->id,
                    'start_date' => $this->contract->start_date,
                    'end_date' => $this->contract->end_date,
                    'monthly_amount' => $this->contract->monthly_amount,
                    'currency' => $this->contract->currency,
                    'status' => $this->contract->status,
                ];
            }),
        ];
    }
}
