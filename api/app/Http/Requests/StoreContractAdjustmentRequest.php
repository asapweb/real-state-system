<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ContractAdjustmentType;

class StoreContractAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date'],
            'type' => ['required', new Enum(ContractAdjustmentType::class)],
            'value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'index_type_id' => ['nullable', 'exists:index_types,id', 'required_if:type,' . ContractAdjustmentType::INDEX->value],
        ];
    }
}
