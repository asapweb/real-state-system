<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Models\ContractExpense;
use App\Models\VoucherType;
use Illuminate\Http\Request;
use App\Http\Resources\ContractResource;
use Carbon\Carbon;
use DB;
class ContractController extends Controller
{
    public function lookup(Request $request)
    {
        $qText    = trim((string) $request->query('q', ''));
        $idParam  = $request->integer('id');
        $clientId = $request->query('client_id');
        $period   = $request->query('period'); // YYYY-MM (usa tu scope activeDuring)
        $limit    = min((int) $request->query('limit', 15), 50);

        // Si tipea "CON-000123" o "123", nos quedamos con el número
        $idFromText = (int) preg_replace('/\D+/', '', $qText);

        // (Opcional pero recomendado) Subquery para elegir un solo tenant por contrato:
        // RN=1 prioriza is_primary desc, luego id asc
        $tenantSub = DB::table('contract_clients as cc')
            ->selectRaw("
                cc.contract_id,
                cc.client_id,
                ROW_NUMBER() OVER (PARTITION BY cc.contract_id ORDER BY cc.is_primary DESC, cc.id ASC) as rn
            ")
            ->where('cc.role', 'tenant');

        $rows = Contract::query()
            // JOINs mínimos
            ->leftJoinSub($tenantSub, 't', fn($j) => $j->on('t.contract_id', '=', 'contracts.id')->where('t.rn', 1))
            ->leftJoin('clients as cli', 'cli.id', '=', 't.client_id')
            ->leftJoin('properties as p', 'p.id', '=', 'contracts.property_id')
            ->leftJoin('neighborhoods as nb', 'nb.id', '=', 'p.neighborhood_id')
            ->leftJoin('cities as ct', 'ct.id', '=', 'p.city_id')

            // Filtros
            ->when($period, fn($q) => $q->activeDuring($period))        // tu scope
            ->when($clientId, fn($q) => $q->where('t.client_id', $clientId))
            ->when($idParam, fn($q) => $q->where('contracts.id', $idParam))
            ->when($qText !== '' && !$idParam, function ($q) use ($qText, $idFromText) {
                $q->where(function ($w) use ($qText, $idFromText) {
                    if ($idFromText > 0) {
                        $w->orWhere('contracts.id', $idFromText);
                    }

                    // Nombre y apellido (no existe columna full_name)
                    $w->orWhere(DB::raw("CONCAT_WS(' ', cli.name, cli.last_name)"), 'like', "%{$qText}%");

                    // Dirección
                    $w->orWhere('p.street', 'like', "%{$qText}%")
                      ->orWhere('p.number', 'like', "%{$qText}%")
                      ->orWhere('nb.name', 'like', "%{$qText}%")
                      ->orWhere('ct.name', 'like', "%{$qText}%");
                });
            })

            // Ranking: si tipeó un número que matchea id, mostrarlo primero
            ->when($idFromText > 0, fn($q) =>
                $q->orderByRaw('CASE WHEN contracts.id = ? THEN 0 ELSE 1 END, contracts.id DESC', [$idFromText])
            , fn($q) => $q->orderBy('contracts.id', 'desc'))

            // SELECT mínimo + campos calculados
            ->limit($limit)
    ->get([
        'contracts.id',
        DB::raw("DATE(contracts.start_date) as start_date"),
        DB::raw("DATE(contracts.end_date)   as end_date"),
        DB::raw("CAST(contracts.status AS CHAR) as status_value"), // 👈 alias distinto
        DB::raw("CONCAT('CON-', LPAD(contracts.id, 6, '0')) as code"),
        DB::raw("TRIM(CONCAT_WS(' ', cli.name, cli.last_name)) as tenant_name"),
        DB::raw("
          TRIM(CONCAT_WS(
            ', ',
            TRIM(CONCAT_WS(' ', p.street, p.number)),
            NULLIF(TRIM(CONCAT_WS(', ',
              NULLIF(IF(p.floor     IS NULL OR p.floor     = '', '', CONCAT('Piso ', p.floor    )), ''),
              NULLIF(IF(p.apartment IS NULL OR p.apartment = '', '', CONCAT('Dto. ' , p.apartment)), '')
            )), ''),
            NULLIF(nb.name, ''),
            NULLIF(ct.name, '')
          )) as address
        "),
    ]);

$data = $rows->map(function ($r) {
    $statusValue = $r->status_value; // 👈 ya es string
    $labels = [
        'draft'     => 'Borrador',
        'active'    => 'Activo',
        'cancelled' => 'Cancelado',
        'finished'  => 'Finalizado',
    ];
    $statusLabel = $labels[$statusValue] ?? ucfirst($statusValue);

    return [
        'id'          => (int) $r->id,
        'code'        => $r->code,
        'tenant_name' => $r->tenant_name ?: 'Inquilino no especificado',
        'address'     => $r->address     ?: 'Propiedad no especificada',
        'status'      => $statusValue,
        'start_date'  => $r->start_date,
        'end_date'    => $r->end_date,
        'label'       => "{$r->code} / " . ($r->tenant_name ?: 'Inquilino no especificado') . " / " . ($r->address ?: 'Propiedad no especificada'),
        'subtitle'    => $statusLabel
            . ($r->start_date ? ' · ' . date('d/m/Y', strtotime($r->start_date)) : '')
            . ($r->end_date ? ' – ' . date('d/m/Y', strtotime($r->end_date)) : ''),
    ];
});

        return response()->json(['data' => $data]);
    }

    public function index(Request $request)
    {
        // Paginación y orden
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'start_date', 'end_date', 'monthly_amount', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = Contract::query();

        // Filtros
        if ($request->filled('search.property_id')) {
            $query->where('property_id', $request->search['property_id']);
        }

        if ($request->filled('search.status')) {
            $query->where('status', $request->search['status']);
        }

        if ($request->has('search.text')) {
            $text = $request->search['text'];
            $query->whereHas('property', function ($q) use ($text) {
                $q->where('street', 'like', "%$text%")
                  ->orWhere('registry_number', 'like', "%$text%");
            });
        }

        $query->with(['property', 'rentalApplication', 'owners.client', 'mainTenant.client', 'collectionBooklet', 'settlementBooklet']);

        $query->orderBy($sortBy, $sortDirection);

        return ContractResource::collection($query->paginate($perPage));
    }

    public function store(StoreContractRequest $request)
    {
        $contract = Contract::create($request->validated());

        return response()->json($contract->load(['property', 'rentalApplication']), 201);
    }

    public function show(Contract $contract)
    {
        return new ContractResource($contract->load(['property', 'rentalApplication', 'attachments']));
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $contract->update($request->validated());

        return response()->json($contract->load(['property', 'rentalApplication']));
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return response()->json(['message' => 'Contrato eliminado correctamente.']);
    }

    public function uncollectedConcepts()
    {
        $period = Carbon::now()->startOfMonth();

        $voucherTypeId = VoucherType::where('short_name', 'COB')->value('id');

        // 1. Contratos activos con alquiler sin cobrar en el mes actual
        $contractsWithMissingRent = \App\Models\Contract::activeDuring($period)
            ->with('clients')
            ->whereDoesntHave('vouchers', function ($q) use ($voucherTypeId, $period) {
                $q->where('voucher_type_id', $voucherTypeId)
                ->where('period', $period)
                ->whereHas('items', fn ($q) => $q->where('type', 'rent'));
            })
            ->get();

        // 2. Gastos incluidos sin voucher
        $unbilledExpenses = ContractExpense::with('contract.clients')
            // ->where('included_in_collection', true)
            ->where('period', $period->format('Y-m'))
            ->whereNull('voucher_id')
            ->get();

        return response()->json([
            'period' => $period->toDateString(),
            'missing_rents' => $contractsWithMissingRent,
            'unbilled_expenses' => $unbilledExpenses,
        ]);
    }

}
