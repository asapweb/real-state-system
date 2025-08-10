<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashMovementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'cash_account_id' => $this->cash_account_id,
            'cash_account' => CashAccountResource::make($this->whenLoaded('cashAccount')),
            'date' => $this->date?->format('Y-m-d'),
            'amount' => number_format($this->amount, 2, '.', ''),
            'currency' => $this->currency,
            'direction' => $this->direction,
            'reference' => $this->reference,
            'voucher_id' => $this->voucher_id,
            'voucher' => VoucherResource::make($this->whenLoaded('voucher')),
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => PaymentMethodResource::make($this->whenLoaded('paymentMethod')),
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
