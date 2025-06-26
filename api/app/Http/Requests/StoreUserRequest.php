<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'person.name' => 'required|string|max:255',
            'person.last_name' => 'required|string|max:255',
            'person.no_document' => 'required|boolean',
            'person.document_number' => 'nullable|string|max:50|unique:people,document_number|required_if:person.no_document,false',
            'person.document_type' => 'nullable|string|max:50|required_if:person.no_document,false',
        ];
    }
}
