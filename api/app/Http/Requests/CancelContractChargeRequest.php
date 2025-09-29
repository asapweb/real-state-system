<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelContractChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3'],
        ];
    }
}
