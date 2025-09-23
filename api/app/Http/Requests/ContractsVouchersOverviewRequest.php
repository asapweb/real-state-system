<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractsVouchersOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m-d'],
        ];
    }
}

