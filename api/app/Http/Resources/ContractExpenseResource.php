<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractExpenseResource extends JsonResource
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
            'amount' => $this->amount,
            'currency' => $this->currency,
            'period' => $this->period,
            'period_formatted' => $this->period_formatted,
            'due_date' => $this->due_date,
            'paid_by' => $this->paid_by,
            'is_paid' => $this->is_paid,
            'paid_at' => $this->paid_at,
            'description' => $this->description,
            'included_in_collection' => $this->included_in_collection,
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
