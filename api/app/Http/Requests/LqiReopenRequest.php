<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LqiReopenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
