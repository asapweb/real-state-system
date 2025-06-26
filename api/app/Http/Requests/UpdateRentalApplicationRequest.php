<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\RentalApplicationStatus;
use App\Enums\InsuranceResponsible;
use App\Enums\Currency;

class UpdateRentalApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_offer_id' => ['nullable', 'exists:rental_offers,id'],
            'property_id' => ['required_without:rental_offer_id', 'exists:properties,id'],
            'applicant_id' => ['required', 'exists:clients,id'],

            'insurance_responsible' => ['nullable', new Enum(InsuranceResponsible::class)],
            'insurance_required_from' => ['nullable', 'date'],

            'status' => ['required', new Enum(RentalApplicationStatus::class)],
            'notes' => ['nullable', 'string'],

            'reservation_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', new Enum(Currency::class)],
        ];
    }
}
