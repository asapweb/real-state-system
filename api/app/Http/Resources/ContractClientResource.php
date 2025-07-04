<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractClientResource extends JsonResource
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
            'client_id' => $this->client_id,
            'role' => $this->role,
            'ownership_percentage' => $this->ownership_percentage,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'last_name' => $this->client->last_name,
                    'company_name' => $this->client->company_name,
                    'client_type' => $this->client->client_type,
                    'document_type' => $this->client->documentType ? [
                        'id' => $this->client->documentType->id,
                        'name' => $this->client->documentType->name,
                    ] : null,
                    'document_number' => $this->client->document_number,
                    'email' => $this->client->email,
                    'cellphone' => $this->client->cellphone,
                ];
            }),

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
