<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountMovementResource extends JsonResource
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
            'voucher_id' => $this->voucher_id,
            'date' => $this->date,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'is_initial' => $this->is_initial,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'client' => $this->whenLoaded('client', fn() => new ClientResource($this->client)),
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
        ];
    }
}
