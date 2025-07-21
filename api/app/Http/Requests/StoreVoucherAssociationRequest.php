<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherAssociationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asumimos middleware de autenticación
    }

    public function rules(): array
    {
        return [
            'voucher_id' => ['required', 'exists:vouchers,id'],
            'associated_voucher_ids' => ['required', 'array', 'min:1'],
            'associated_voucher_ids.*' => ['required', 'exists:vouchers,id'],
        ];
    }
}
