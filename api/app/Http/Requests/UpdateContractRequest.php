<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ContractStatus;
use App\Enums\CommissionType;
use App\Enums\CommissionPayer;
use App\Enums\DepositType;
use App\Enums\DepositHolder;
use App\Enums\PenaltyType;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'rental_application_id' => ['nullable', 'exists:rental_applications,id'],

            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'monthly_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_day' => ['nullable', 'integer', 'between:1,31'],

            'commission_type' => ['required', new Enum(CommissionType::class)],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'commission_payer' => ['nullable', new Enum(CommissionPayer::class)],

            'insurance_required' => ['required', 'boolean'],
            'insurance_amount' => ['nullable', 'numeric', 'min:0'],
            'insurance_company_name' => ['nullable', 'string', 'max:255'],

            'owner_share_percentage' => ['required', 'numeric', 'between:0,100'],

            'prorate_first_month' => ['required', 'boolean'],
            'prorate_last_month' => ['required', 'boolean'],
            'is_one_time' => ['required', 'boolean'],

            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_currency' => ['nullable', 'string', 'size:3'],
            'deposit_type' => ['required', new Enum(DepositType::class)],
            'deposit_holder' => ['nullable', new Enum(DepositHolder::class)],

            'has_penalty' => ['required', 'boolean'],
            'penalty_type' => ['required', new Enum(PenaltyType::class)],
            'penalty_value' => ['nullable', 'numeric', 'min:0'],
            'penalty_grace_days' => ['required', 'integer', 'min:0'],

            'status' => ['required', new Enum(ContractStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
