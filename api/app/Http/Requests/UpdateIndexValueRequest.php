<?php

namespace App\Http\Requests;

use App\Models\IndexType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndexValueRequest extends FormRequest
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
        $indexValue = $this->route('indexValue');
        $indexTypeId = $this->input('index_type_id') ?? $indexValue->index_type_id;
        return [
            'index_type_id' => 'sometimes|required|exists:index_types,id',
            'effective_date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('index_values')->where(function ($query) use ($indexTypeId, $indexValue) {
                    return $query->where('index_type_id', $indexTypeId)->where('id', '!=', $indexValue->id);
                }),
            ],
            'value' => 'sometimes|required|numeric|min:0',
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        // Eliminamos la validación duplicada ya que está manejada en las reglas principales
        // con Rule::unique que ya excluye el registro actual
    }
}
