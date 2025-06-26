<?php

namespace App\Http\Controllers;

use App\Models\Client; // Importa el modelo Client
use App\Models\BillingDetail; // Importa el modelo BillingDetail
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Para reglas de validación

class BillingDetailController extends Controller
{
    /**
     * Muestra una lista de detalles de facturación para un cliente específico.
     */
    public function index(Client $client)
    {
        // Cargar las relaciones necesarias para el frontend (ej. taxCondition, documentType)
        $billingDetails = $client->billingDetails()->with(['taxCondition', 'documentType'])->get();
        return response()->json($billingDetails);
    }

    /**
     * Almacena un nuevo detalle de facturación para un cliente específico.
     */
    public function store(Request $request, Client $client)
    {
        $validatedData = $request->validate([
            'billing_name'     => ['required', 'string', 'max:255'],
            'tax_condition_id' => ['required', 'exists:tax_conditions,id'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'document_number'  => [
                'required',
                'string',
                'max:50',
                // Asegurar unicidad del número de documento dentro del cliente (opcional, depende del negocio)
                // Rule::unique('billing_details')->where(function ($query) use ($client) {
                //     return $query->where('client_id', $client->id);
                // })
            ],
            'billing_address'  => ['nullable', 'string', 'max:255'],
            'is_default'       => ['boolean'],
        ]);

        // Antes de crear, manejar la lógica de is_default
        if (isset($validatedData['is_default']) && $validatedData['is_default']) {
            // Desmarcar todos los demás detalles de facturación como predeterminados para este cliente
            $client->billingDetails()->update(['is_default' => false]);
        }

        $billingDetail = $client->billingDetails()->create($validatedData);

        // Volver a cargar las relaciones para la respuesta
        $billingDetail->load(['taxCondition', 'documentType']);

        return response()->json($billingDetail, 201); // 201 Created
    }

    /**
     * Muestra el detalle de facturación especificado.
     */
    public function show(Client $client, BillingDetail $billingDetail)
    {
        // Asegurarse de que el detalle de facturación pertenezca al cliente correcto
        if ($billingDetail->client_id !== $client->id) {
            abort(404, 'Detalle de facturación no encontrado para este cliente.');
        }
        $billingDetail->load(['taxCondition', 'documentType']);
        return response()->json($billingDetail);
    }

    /**
     * Actualiza el detalle de facturación especificado en el almacenamiento.
     */
    public function update(Request $request, Client $client, BillingDetail $billingDetail)
    {
        // Asegurarse de que el detalle de facturación pertenezca al cliente correcto
        if ($billingDetail->client_id !== $client->id) {
            abort(404, 'Detalle de facturación no encontrado para este cliente.');
        }

        $validatedData = $request->validate([
            'billing_name'     => ['required', 'string', 'max:255'],
            'tax_condition_id' => ['required', 'exists:tax_conditions,id'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'document_number'  => [
                'required',
                'string',
                'max:50',
                // Asegurar unicidad ignorando el propio ID del detalle de facturación
                // Rule::unique('billing_details')->where(function ($query) use ($client) {
                //     return $query->where('client_id', $client->id);
                // })->ignore($billingDetail->id)
            ],
            'billing_address'  => ['nullable', 'string', 'max:255'],
            'is_default'       => ['boolean'],
        ]);

        // Antes de actualizar, manejar la lógica de is_default
        if (isset($validatedData['is_default']) && $validatedData['is_default']) {
            // Desmarcar todos los demás detalles de facturación como predeterminados para este cliente
            // Excluir el detalle actual si ya era el predeterminado
            $client->billingDetails()->where('id', '!=', $billingDetail->id)->update(['is_default' => false]);
        } else {
             // Si se está desmarcando el detalle actual como por defecto
             // Y si no hay otro por defecto, se podría considerar una lógica para asignar uno
             // (o dejarlo sin predeterminado, dependiendo de la regla de negocio)
        }

        $billingDetail->update($validatedData);
        $billingDetail->load(['taxCondition', 'documentType']); // Volver a cargar las relaciones para la respuesta

        return response()->json($billingDetail);
    }

    /**
     * Elimina el detalle de facturación especificado del almacenamiento.
     */
    public function destroy(Client $client, BillingDetail $billingDetail)
    {
        // Asegurarse de que el detalle de facturación pertenezca al cliente correcto
        if ($billingDetail->client_id !== $client->id) {
            abort(404, 'Detalle de facturación no encontrado para este cliente.');
        }

        $billingDetail->delete();

        return response()->json(null, 204); // 204 No Content
    }
}
