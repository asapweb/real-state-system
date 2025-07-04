<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'booklet_id' => $this->booklet_id,
            'number' => $this->number,
            'issue_date' => $this->issue_date,
            'period' => $this->period,
            'due_date' => $this->due_date,
            'client_id' => $this->client_id,
            'contract_id' => $this->contract_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'total' => $this->total,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'cae' => $this->cae,
            'cae_expires_at' => $this->cae_expires_at,
            'subtotal_taxed' => $this->subtotal_taxed,
            'subtotal_untaxed' => $this->subtotal_untaxed,
            'subtotal_exempt' => $this->subtotal_exempt,
            'subtotal_vat' => $this->subtotal_vat,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'items' => $this->whenLoaded('items', fn() => VoucherItemResource::collection($this->items)),
            'payments' => $this->whenLoaded('payments', fn() => VoucherPaymentResource::collection($this->payments)),
            'applications' => $this->whenLoaded('applications', fn() => VoucherApplicationResource::collection($this->applications)),
            'booklet' => $this->whenLoaded('booklet', fn() => new BookletResource($this->booklet)),
            'client' => $this->whenLoaded('client', fn() => new ClientResource($this->client)),
            'contract' => $this->whenLoaded('contract', fn() => new ContractResource($this->contract)),
        ];
    }
}
