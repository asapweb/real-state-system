<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajustar si usas policies/permissions
    }

    public function rules(): array
    {
        return [
            'period'      => ['required'], // enviar YYYY-MM-01
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'dry_run'     => ['sometimes', 'boolean'],          // opcional para simular
        ];
    }
}
