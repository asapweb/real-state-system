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
            'running_balance' => $this->running_balance ?? 0,

            // Información del voucher
            'voucher_number' => $this->voucher?->number,
            'voucher_type' => $this->voucher?->booklet?->voucherType?->name,
            'voucher_formatted_number' => $this->voucher ?
                $this->voucher->booklet->getFormattedVoucherNumber($this->voucher->number) : null,

            // Relaciones
            'client' => $this->whenLoaded('client', fn() => new ClientResource($this->client)),
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
        ];
    }
}
