<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Models\ChargeType;
use App\Models\Contract;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\VoucherCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LqiBuilderService
{
    protected $calc;
    protected $voucherService;

    public function __construct(
        VoucherCalculationService $calc,
        \App\Services\VoucherService $voucherService
    ) {
        $this->calc = $calc;
        $this->voucherService = $voucherService;
    }

    /**
     * Sincroniza (crea/actualiza) el borrador de LQI para (contrato, período, moneda).
     * - Idempotente: no duplica voucher ni items.
     * - No emite, no marca cargos asentados (eso ocurre en issue).
     */
    public function sync(Contract $contract, string $period, string $currency): Voucher
    {
        $from = Carbon::parse($period . '-01')->startOfMonth();
        $to   = (clone $from)->addMonth();

        $currency = strtoupper($currency);

        return DB::transaction(function () use ($contract, $from, $to, $currency) {
            $this->assertNotBlocked($contract, $from, $currency);

            // 0) Tipo LQI
            $voucherTypeId = VoucherType::query()
                ->where('short_name', 'LQI')
                ->value('id');

            if (!$voucherTypeId) {
                throw ValidationException::withMessages([
                    'voucher_type' => 'Voucher type LQI no está configurado.',
                ]);
            }

            // 1) Unicidad activa (draft|issued) por (contract, period, currency)
            $active = Voucher::query()
                ->where('voucher_type_id', $voucherTypeId)
                ->where('contract_id', $contract->id)
                ->whereDate('period', $from->toDateString())
                ->where('currency', strtoupper($currency))
                ->whereIn('status', [VoucherStatus::Draft->value, VoucherStatus::Issued->value])
                ->orderByDesc('id')
                ->get();

            /** @var Voucher|null $issued */
            $issued = $active->first(fn (Voucher $voucher) => $voucher->status === VoucherStatus::Issued);
            if ($issued) {
                // v1: si está emitido, solo se permite reabrir por endpoint específico (y sin recibos).
                throw ValidationException::withMessages([
                    'period' => 'Período ya emitido para esta moneda. Reabrir la LQI si no tiene recibos.',
                ]);
            }

            /** @var Voucher|null $draft */
            $draft = $active->first(fn (Voucher $voucher) => $voucher->status === VoucherStatus::Draft);

            // 1.5) Determinar cargos elegibles antes de crear/actualizar el draft
            $eligible = $this->eligibleCharges($contract->id, $from, $to, $currency);
            if ($eligible->isEmpty()) {
                throw ValidationException::withMessages([
                    'no_eligible' => 'No hay cargos elegibles para generar la liquidación del período.',
                ]);
            }

            $bookletId = $this->resolveBookletIdForLqi($contract);
            $tenant    = $this->resolveTenantClientSnapshot($contract); // client_id + snapshot

            if (!$tenant['client_id']) {
                throw ValidationException::withMessages([
                    'client_id' => 'El contrato no tiene inquilino principal asignado.',
                ]);
            }

            $basePayload = array_merge([
                'booklet_id'                => $bookletId,
                'voucher_type_short_name'   => 'LQI',
                'contract_id'               => $contract->id,
                'issue_date'                => now()->toDateString(),
                'period'                    => $from->toDateString(),
                'currency'                  => strtoupper($currency),
                'generated_from_collection' => false,
            ], $tenant);

            // Construir payload de items desde cargos elegibles
            $itemsPayload = $eligible->map(function (ContractCharge $charge) {
                $impact = optional($charge->chargeType)->tenant_impact;
                return [
                    'type'               => 'charge',
                    'description'        => $this->buildItemDescription($charge),
                    'quantity'           => 1,
                    'unit_price'         => $charge->amount,
                    'tax_rate_id'        => null, // mapear si aplica (desde charge_type o charge)
                    'contract_charge_id' => $charge->id,
                    'impact'             => $impact instanceof \App\Enums\ChargeImpact
                        ? $impact->value
                        : ($impact ?? 'add'), // 'add'|'subtract'
                ];
            })->values()->all();
            $payload = array_merge($basePayload, ['items' => $itemsPayload]);

            // 2) Crear draft si no existe
            if (!$draft) {
                $draft = $this->voucherService->createFromArray($payload);
            }

            \Log::info('- LqiBuilderService : UPDATE FROM ARRAY  -------------------------------');
            \Log::info('- LqiBuilderService : DATA PAYLOAD  -------------------------------');
            \Log::info('- LqiBuilderService : dataPayload', ['dataPayload' => $payload]);

            // 5) Actualizar draft con items (idempotente) vía VoucherService
            $draft = $this->voucherService->updateFromArray($draft, $payload);

            // 6) Devolver con relaciones
            return $draft->load(['items.taxRate', 'booklet.voucherType']);
        });
    }

    /**
     * Query de elegibilidad v1 (lado inquilino)
     */
    protected function eligibleCharges(int $contractId, Carbon $from, Carbon $to, string $currency): Collection
    {
        return ContractCharge::query()
            ->where('contract_id', $contractId)
            ->whereDate('effective_date', '>=', $from->toDateString())
            ->whereDate('effective_date', '<',  $to->toDateString())
            ->where('currency', strtoupper($currency))
            ->where('is_canceled', false)
            ->whereNull('tenant_liquidation_settled_at')
            ->whereHas('chargeType', function ($q) {
                $q->where('tenant_impact', 'add');
            })
            // Opcional: lock optimista si hay procesos paralelos de sync
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * Texto amigable para la UI; ajustá a tus tipos.
     */
    protected function buildItemDescription(ContractCharge $charge): string
    {
        $type = optional($charge->chargeType)->name ?? 'Cargo';
        $ym   = Carbon::parse($charge->effective_date)->format('Y-m');
        return "{$type} {$ym}" . ($charge->description ? " – {$charge->description}" : '');
    }

    /**
     * Talonario para LQI (inquilino).
     * Por defecto uso el "de cobranzas" del contrato; ajustá si tenés uno específico para LQI.
     */
    protected function resolveBookletIdForLqi(Contract $contract): int
    {
        // Preferimos el talonario de cobranzas; si no, el de liquidaciones; si no, fallback 1
        return $contract->collection_booklet_id
            ?? $contract->settlement_booklet_id
            ?? 12;
    }

    /**
     * Resuelve el inquilino principal y prepara snapshot para el voucher.
     * Devuelve: client_id (obligatorio) y snapshot opcional (name, address, etc. si existen en Client).
     */
    protected function resolveTenantClientSnapshot(Contract $contract): array
    {
        // Cargar relación mainTenant->client
        $tenantContractClient = $contract->mainTenant()->with('client')->first();

        $clientId = $tenantContractClient ? $tenantContractClient->client_id : null;
        $client   = optional($tenantContractClient)->client; // puede tener name, address, etc.

        return [
            'client_id'                => $clientId,
            'client_name'              => optional($client)->name,
            'client_address'           => optional($client)->address,
            'client_document_type_name'=> optional($client)->document_type_name,
            'client_document_number'   => optional($client)->document_number,
            'client_tax_condition_name'=> optional($client)->tax_condition_name,
            'client_tax_id_number'     => optional($client)->tax_id_number,
        ];
    }

    protected function assertNotBlocked(Contract $contract, Carbon $period, string $currency): void
    {
        if ($contract->adjustments()->blockingForPeriod($period)->exists()) {
            throw ValidationException::withMessages([
                'pending_adjustment' => 'No se puede generar la liquidación: hay un ajuste pendiente en el período.',
            ]);
        }

        $requireRentBeforeAny = config('features.lqi.require_rent_before_any_lqi', false);
        $rentCurrency = $this->rentCurrencyForContract($contract);

        if ($requireRentBeforeAny) {
            if (!$this->hasRentChargeForPeriod($contract->id, $period)) {
                throw ValidationException::withMessages([
                    'missing_rent' => 'No se puede generar la liquidación: falta la cuota de renta del período.',
                ]);
            }

            return;
        }

        if ($rentCurrency && $currency === $rentCurrency) {
            if (!$this->hasRentChargeForPeriod($contract->id, $period, $rentCurrency)) {
                throw ValidationException::withMessages([
                    'missing_rent' => "No se puede generar la liquidación: falta la cuota de renta del período en {$rentCurrency}.",
                ]);
            }

            return;
        }

        if ($rentCurrency && $currency !== $rentCurrency) {
            \Log::info('LqiBuilderService.missing_rent.allowed_other_currency', [
                'contract_id' => $contract->id,
                'period' => $period->format('Y-m'),
                'lqi_currency' => $currency,
                'rent_currency' => $rentCurrency,
            ]);
        }
    }

    protected function rentCurrencyForContract(Contract $contract): ?string
    {
        $currency = $contract->currency ? strtoupper($contract->currency) : null;
        return $currency ?: null;
    }

    protected function hasRentChargeForPeriod(int $contractId, Carbon $period, ?string $currency = null): bool
    {
        $start = $period->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        return ContractCharge::query()
            ->where('contract_id', $contractId)
            ->whereDate('effective_date', '>=', $start->toDateString())
            ->whereDate('effective_date', '<', $end->toDateString())
            ->where('is_canceled', false)
            ->when($currency, function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(currency)'), strtoupper($currency));
            })
            ->whereNull('tenant_liquidation_settled_at')
            ->whereHas('chargeType', function ($query) {
                $query->where('code', ChargeType::CODE_RENT);
            })
            ->exists();
    }
}
