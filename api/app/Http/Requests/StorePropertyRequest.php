<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PropertyStatus;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_type_id'     => 'required|exists:property_types,id',
            'street'               => 'required|string|max:100',
            'number'               => 'nullable|string|max:10',
            'floor'                => 'nullable|string|max:10',
            'apartment'            => 'nullable|string|max:10',
            'postal_code'          => 'nullable|string|max:20',

            'country_id'           => 'required|exists:countries,id',
            'state_id'             => 'required|exists:states,id',
            'city_id'              => 'required|exists:cities,id',
            'neighborhood_id'      => 'required|exists:neighborhoods,id',

            'tax_code'             => 'nullable|string|max:255',
            'cadastral_reference'  => 'nullable|string|max:255',
            'registry_number'      => 'nullable|string|max:255',

            'has_parking'          => 'boolean',
            'parking_details'      => 'nullable|string',
            'allows_pets'          => 'boolean',
            'iva_condition'        => 'nullable|string|max:255',

            'status'               => ['required', Rule::in(array_column(PropertyStatus::cases(), 'value'))],
            'observations'         => 'nullable|string',
        ];
    }
}
