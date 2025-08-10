<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'contract' => new ContractResource($this->whenLoaded('contract')),

            'service_type_id' => $this->service_type_id,
            'service_type' => new ServiceTypeResource($this->whenLoaded('serviceType')),

            'amount' => $this->amount,
            'currency' => $this->currency,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),

            'paid_by' => $this->paid_by->value,
            'responsible_party' => $this->responsible_party->value,
            'is_paid' => $this->is_paid,
            'paid_at' => $this->paid_at?->format('Y-m-d'),

            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],

            'included_in_voucher' => $this->included_in_voucher,
            'description' => $this->description,

            // Vínculos con vouchers
            'voucher_id' => $this->voucher_id,
            'voucher' => new VoucherResource($this->whenLoaded('voucher')),

            'generated_credit_note_id' => $this->generated_credit_note_id,
            'generated_credit_note' => new VoucherResource($this->whenLoaded('generatedCreditNote')),

            'liquidation_voucher_id' => $this->liquidation_voucher_id,
            'liquidation_voucher' => new VoucherResource($this->whenLoaded('liquidationVoucher')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),

            'settled_at' => $this->settled_at?->format('Y-m-d H:i:s'),

            // Flag calculado
            'is_locked' => $this->is_locked,
        ];
    }
}
