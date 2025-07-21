<?php

namespace App\Http\Requests;

use App\Models\IndexType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndexValueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'index_type_id' => 'required|exists:index_types,id',
            'effective_date' => [
                'required',
                'date',
                Rule::unique('index_values')->where(function ($query) {
                    return $query->where('index_type_id', $this->input('index_type_id'));
                }),
            ],
            'value' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'index_type_id.required' => 'El tipo de índice es requerido.',
            'index_type_id.exists' => 'El tipo de índice seleccionado no existe.',
            'effective_date.required' => 'La fecha es requerida.',
            'effective_date.date' => 'La fecha debe ser válida.',
            'effective_date.unique' => 'Ya existe un valor para este tipo de índice en la fecha indicada.',
            'value.required' => 'El valor es requerido.',
            'value.numeric' => 'El valor debe ser un número.',
            'value.min' => 'El valor debe ser mayor o igual a 0.',
        ];
    }
}
