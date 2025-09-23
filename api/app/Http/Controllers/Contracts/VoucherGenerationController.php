<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractsVouchersOverviewRequest;
use App\Http\Requests\ContractsVouchersListRequest;
use App\Http\Requests\GenerateVouchersRequest;
use App\Services\Vouchers\VoucherOverviewService;
use App\Services\Vouchers\VoucherListService;
use App\Services\Vouchers\VoucherGenerationService as VouchersGenerator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class VoucherGenerationController extends Controller
{
    public function overview(ContractsVouchersOverviewRequest $request): JsonResponse
    {
        $period = Carbon::parse($request->validated('period'))->startOfMonth();
        $data = app(VoucherOverviewService::class)->build($period);
        return response()->json($data);
    }

    public function list(ContractsVouchersListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $period = Carbon::parse($validated['period'])->startOfMonth();
        $paginator = app(VoucherListService::class)->paginate($period, $validated);
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function generate(GenerateVouchersRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $period = Carbon::parse($validated['period'])->startOfMonth();
        $contractIds = $validated['contract_ids'] ?? null;
        $options = $validated['options'] ?? [];

        $result = app(VouchersGenerator::class)->generate($period, $contractIds, $options);
        return response()->json($result);
    }
}

