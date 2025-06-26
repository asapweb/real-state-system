<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'person.name' => 'required|string|max:255',
            'person.last_name' => 'required|string|max:255',

            'person.document_number' => 'required|string|max:50|unique:people,document_number,' . optional($this->user->person)->id,
            'person.document_type' => 'required|string|max:50',
        ];
    }
}
