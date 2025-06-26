<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ServicePayer;

class UpdateRentalOfferServiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'has_debt' => ['boolean'],
            'debt_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_by' => ['nullable', new Enum(ServicePayer::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
