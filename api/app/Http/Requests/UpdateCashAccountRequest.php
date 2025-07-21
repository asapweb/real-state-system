<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('cash_account');
        if (is_object($id)) {
            $id = $id->id;
        }
        return [
            'name' => 'required|string|max:255|unique:cash_accounts,name,' . $id,
            'type' => 'required|in:cash,bank,virtual',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ];
    }
}
