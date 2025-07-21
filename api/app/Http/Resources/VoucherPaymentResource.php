<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VoucherPaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'voucher_id' => $this->voucher_id,
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => PaymentMethodResource::make($this->whenLoaded('paymentMethod')),
            'cash_account_id' => $this->cash_account_id,
            'cash_account' => CashAccountResource::make($this->whenLoaded('cashAccount')),
            'amount' => number_format($this->amount, 2, '.', ''),
            'reference' => $this->reference,
            'created_at' => $this->created_at,
        ];
    }
}
