<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:cash_accounts,name',
            'type' => 'required|in:cash,bank,virtual',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ];
    }
}
