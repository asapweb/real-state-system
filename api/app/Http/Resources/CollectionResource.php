<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
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
            'client_id' => $this->client_id,
            'contract_id' => $this->contract_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'period' => $this->period,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at,
            'paid_by_user_id' => $this->paid_by_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Relaciones
            'client' => new ClientResource($this->whenLoaded('client')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'items' => CollectionItemResource::collection($this->whenLoaded('items')),
            'paid_by_user' => new UserResource($this->whenLoaded('paidByUser')),
        ];
    }
}
