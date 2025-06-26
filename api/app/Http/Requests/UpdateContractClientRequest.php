<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ContractClientRole;

class UpdateContractClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'exists:clients,id'],
            'role' => ['sometimes', 'required', new Enum(ContractClientRole::class)],
            'ownership_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'is_primary' => ['boolean'],
        ];
    }
}
