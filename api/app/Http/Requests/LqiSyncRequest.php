<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LqiSyncRequest extends FormRequest
{
    public function authorize(): bool { return true; } // agrega policies si corresponde

    public function rules(): array
    {
        return [
            'period'   => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // YYYY-MM
            'currency' => ['required', 'string', 'size:3'], // ej ARS, USD
        ];
    }

    public function messages(): array
    {
        return [
            'period.regex' => 'El período debe tener formato YYYY-MM.',
        ];
    }
}
