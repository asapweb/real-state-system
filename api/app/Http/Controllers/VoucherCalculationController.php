<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\VoucherPayment;
use App\Models\VoucherApplication;
use App\Models\TaxRate;
use App\Models\VoucherType;
use App\Services\VoucherCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VoucherCalculationController extends Controller
{
    protected $calculationService;

    public function __construct(VoucherCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function previewTotals(Request $request): JsonResponse
    {
        $data = $request->all();

        $voucher = new Voucher([
            'voucher_type_short_name' => $data['voucher_type_short_name'] ?? null,
            'currency' => $data['currency'] ?? 'ARS',
        ]);

        // Cargar ítems (si hay)
        $voucher->items = collect($data['items'] ?? [])
            ->map(function ($itemData) {
                return new VoucherItem($itemData);
            });

        // Cargar las tasas para ítems
        $taxRateIds = collect($voucher->items)->pluck('tax_rate_id')->filter()->unique();
        $taxRates = TaxRate::whereIn('id', $taxRateIds)->get()->keyBy('id');

        foreach ($voucher->items as $item) {
            if ($item->tax_rate_id) {
                $item->setRelation('taxRate', $taxRates->get($item->tax_rate_id));
            }
        }

        // Cargar pagos (si hay)
        $voucher->payments = collect($data['payments'] ?? [])
            ->map(fn ($payment) => new VoucherPayment($payment));

        // throw ValidationException::withMessages([
        //     'applications' => $data['applications'],
        // ]);

        // Cargar aplicaciones (si hay)
        $voucher->applications = collect($data['applications'] ?? [])
            ->map(function ($app) {
                return new VoucherApplication($app);
            });

            // throw ValidationException::withMessages([
            //     'voucher->applications' => $voucher->applications,
            // ]);
        // Cargar los comprobantes a los que se aplica (para calcular signo)
        $appliedIds = $voucher->applications->pluck('applied_to_id')->unique()->filter();
        $appliedVouchers = Voucher::with('voucherType')->whereIn('id', $appliedIds)->get()->keyBy('id');

        foreach ($voucher->applications as $app) {
            $app->appliedVoucher = $appliedVouchers->get($app->applied_to_id);
        }

        // throw ValidationException::withMessages([
        //     'voucher' => $voucher,
        // ]);

        // Calcular totales
        $this->calculationService->calculateVoucher($voucher);

        return response()->json([
            'voucher' => $voucher,
            'subtotal_exempt' => $voucher->subtotal_exempt,
            'subtotal_untaxed' => $voucher->subtotal_untaxed,
            'subtotal_taxed' => $voucher->subtotal_taxed,
            'subtotal_vat' => $voucher->subtotal_vat,
            'subtotal_other_taxes' => $voucher->subtotal_other_taxes,
            'total' => $voucher->total,
        ]);
    }
}
