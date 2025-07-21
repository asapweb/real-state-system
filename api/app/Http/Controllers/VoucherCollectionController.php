<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Http\Resources\CollectionResource;
use App\Services\CollectionGenerationService;
use Illuminate\Support\Carbon;

class VoucherCollectionController extends Controller
{
    public function index(Request $request)
    {
        // Parámetros
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search', []);
        $sortBy = $request->input('sort_by', 'issue_date');
        $sortDirection = $request->input('sort_direction', 'desc');

        // ID del tipo COB (puede ser hardcodeado o cacheado)
        $voucherTypeCob = config('vouchers.types.cob', 1); // o usar un Enum

        // Validación básica del orden
        $sortable = ['id', 'issue_date', 'due_date', 'client_name', 'status', 'total'];
        if (!in_array($sortBy, $sortable)) {
            $sortBy = 'issue_date';
        }

        $query = Voucher::query()
            ->where('voucher_type_id', $voucherTypeCob)
            ->with(['client', 'items']) // ajustar relaciones necesarias
            ->when($search['client_name'] ?? null, fn($q, $value) =>
                $q->whereHas('client', fn($q) =>
                    $q->where('name', 'like', "%{$value}%")
                )
            )
            ->when($search['status'] ?? null, fn($q, $value) =>
                $q->where('status', $value)
            )
            ->when($search['id'] ?? null, fn($q, $value) =>
                $q->where('id', $value)
            )
            ->orderBy($sortBy, $sortDirection);

        return CollectionResource::collection($query->paginate($perPage));
    }

    public function preview(Request $request, CollectionGenerationService $service)
    {
        $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->period)->startOfMonth();

        return response()->json($service->previewForMonth($period));
    }


    public function generate(Request $request, CollectionGenerationService $service)
    {
        \Log::debug('Periodo recibido', ['period' => $request->input('period')]);

        $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $period = Carbon::parse($request->input('period'))->startOfMonth();

        try {
            $collections = $service->generateForMonth($period);
            return response()->json([ 'message' => 'debug', ], 422);

            // Obtener los vouchers relacionados a las collections generadas
            $vouchers = $collections->map(fn($collection) => $collection->voucher)->filter();

            return response()->json([
                'message' => 'Se generaron ' . $vouchers->count() . ' cobranzas para ' . $period->translatedFormat('F Y'),
                'generated' => CobranzaResource::collection($vouchers),
            ]);
        } catch (CollectionGenerationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        }
    }

}
