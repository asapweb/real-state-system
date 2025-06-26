<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar si usás políticas
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'currency' => ['required', 'string', Rule::in(['ARS', 'USD'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
