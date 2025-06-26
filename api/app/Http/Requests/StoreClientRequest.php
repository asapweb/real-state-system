<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:150',
            'last_name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150|unique:clients,email',
            'no_document' => 'nullable|boolean',
            'document_type_id' => 'nullable|required_if:no_document,false|exists:document_types,id',
            'document_number' => [
                'nullable', // Allow null or empty when not required
                'required_if:no_document,false',
                'string',
                'max:30',
                'unique:clients,document_number',
            ],
            'tax_document_type_id' => 'nullable|exists:document_types,id',
            'tax_document_number' => 'nullable|string|max:30',
            'tax_condition_id' => 'nullable|exists:tax_conditions,id',

            'phone' => 'nullable|phone:AR',
            'address' => 'nullable|string|max:255',
            'civil_status_id' => 'nullable|exists:civil_statuses,id',
            'nationality_id' => 'nullable|exists:countries,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.phone' => 'El número de teléfono ingresado no es válido para Argentina.',
        ];
    }
}
