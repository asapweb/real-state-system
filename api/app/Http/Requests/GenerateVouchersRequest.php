<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVouchersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m-d'],
            'contract_ids' => ['nullable', 'array'],
            'contract_ids.*' => ['integer'],
            'options' => ['nullable', 'array'],
            'options.create_or_sync' => ['nullable', 'in:sync,create'],
            'options.require_pag_int' => ['nullable', 'boolean'],
            'options.include_bonifications' => ['nullable', 'boolean'],
            'options.dry_run' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $options = $this->input('options', []);
        $this->merge([
            'options' => array_merge([
                'create_or_sync' => 'sync',
                'require_pag_int' => true,
                'include_bonifications' => true,
                'dry_run' => false,
            ], is_array($options) ? $options : []),
        ]);
    }
}

