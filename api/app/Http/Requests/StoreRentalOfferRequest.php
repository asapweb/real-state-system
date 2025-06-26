<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Currency;
use App\Enums\RentalOfferStatus;

class StoreRentalOfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', new Enum(Currency::class)],
            'duration_months' => ['required', 'integer', 'min:1'],
            'availability_date' => ['nullable', 'date'],
            'common_expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'seal_required' => ['boolean'],
            'seal_amount' => ['nullable', 'numeric', 'min:0'],
            'seal_currency' => [
                'nullable',
                'required_if:seal_required,true',
                new Enum(Currency::class)
            ],
            'seal_percentage_owner' => ['nullable', 'numeric', 'between:0,100'],
            'seal_percentage_tenant' => ['nullable', 'numeric', 'between:0,100'],
            'includes_insurance' => ['boolean'],
            'insurance_quote_amount' => ['nullable', 'numeric', 'min:0'],
            'commission_policy' => ['nullable', 'string'],
            'deposit_policy' => ['nullable', 'string'],
            'allow_pets' => ['boolean'],
            'status' => ['required', new Enum(RentalOfferStatus::class)],
            'published_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
