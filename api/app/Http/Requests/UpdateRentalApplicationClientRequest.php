<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\RentalApplicationClientRole;
use App\Enums\Currency;

class UpdateRentalApplicationClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'role' => ['required', new Enum(RentalApplicationClientRole::class)],
            'relationship' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string'],
            'employer' => ['nullable', 'string'],
            'income' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', new Enum(Currency::class)],
            'seniority' => ['nullable', 'string'],
            'is_property_owner' => ['boolean'],
            'marital_status' => ['nullable', 'string'],
            'spouse_name' => ['nullable', 'string'],
            'nationality' => ['nullable', 'string'],
        ];
    }
}
