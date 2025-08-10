<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Http\Resources\VoucherResource;

class VoucherAssociationController extends Controller
{

    public function store(StoreVoucherAssociationRequest $request)
    {
        $voucher = Voucher::findOrFail($request->voucher_id);

        DB::transaction(function () use ($request, $voucher) {
            foreach ($request->associated_voucher_ids as $associatedId) {
                $associated = Voucher::findOrFail($associatedId);

                // Validar la asociación
                VoucherAssociationValidator::validate($voucher, $associated);

                // Crear si no existe
                VoucherAssociation::firstOrCreate([
                    'voucher_id' => $voucher->id,
                    'associated_voucher_id' => $associated->id,
                ]);
            }
        });

        return response()->json(['success' => true], 201);
    }

    /**
     * Devuelve los comprobantes que pueden ser asociados a una NC o ND.
     */
    public function associable(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:N/C,N/D'],
            'client_id' => ['required', 'exists:clients,id'],
            'letter' => ['required'],
            'search' => ['nullable', 'string'],
        ]);

        $query = Voucher::query()
            ->where('client_id', $validated['client_id'])
            ->where('voucher_type_letter', $validated['letter'])
            ->where('status', 'issued');

        // Filtrar por búsqueda si se proporciona
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%");
            });
        }

        if ($validated['type'] === 'N/C') {
            // NC puede asociar a FAC y ND
            $query->whereIn('voucher_type_short_name', ['FAC', 'N/D']);
        } elseif ($validated['type'] === 'N/D') {
            // ND solo puede asociar a FAC
            $query->where('voucher_type_short_name', 'FAC');
        }

        $vouchers = $query
            ->orderByDesc('issue_date')
            ->limit(50)
            ->get();

        // return response()->json($vouchers);
        return VoucherResource::collection($vouchers);

    }
}
