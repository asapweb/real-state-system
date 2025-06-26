<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ServiceType;
use App\Enums\ServicePayer;

class StoreContractServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['required', new Enum(ServiceType::class)],
            'account_number' => ['nullable', 'string', 'max:100'],
            'provider_name' => ['nullable', 'string', 'max:100'],
            'owner_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'has_debt' => ['required', 'boolean'],
            'debt_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_by' => ['nullable', new Enum(ServicePayer::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
} 
