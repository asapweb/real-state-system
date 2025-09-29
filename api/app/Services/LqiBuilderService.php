<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Voucher;
use App\Models\ContractCharge;
use App\Models\VoucherType;
use App\Services\VoucherCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LqiBuilderService
{
    public function __construct(
        private readonly VoucherCalculationService $calc,
        private readonly \App\Services\VoucherService $voucherService,
    ) {}

    /**
     * Sincroniza (crea/actualiza) el borrador de LQI para (contrato, período, moneda).
     * - Idempotente: no duplica voucher ni items.
     * - No emite, no marca cargos asentados (eso ocurre en issue).
     */
    public function sync(Contract $contract, string $period, string $currency): Voucher
    {
        $from = Carbon::parse($period . '-01')->startOfMonth();
        $to   = (clone $from)->addMonth();

        return DB::transaction(function () use ($contract, $from, $to, $currency) {
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
                ->whereIn('status', ['draft', 'issued'])
                ->orderByDesc('id')
                ->get();

            /** @var Voucher|null $issued */
            $issued = $active->firstWhere('status', 'issued');
            if ($issued) {
                // v1: si está emitido, solo se permite reabrir por endpoint específico (y sin recibos).
                throw ValidationException::withMessages([
                    'period' => 'Período ya emitido para esta moneda. Reabrir la LQI si no tiene recibos.',
                ]);
            }

            /** @var Voucher|null $draft */
            $draft = $active->firstWhere('status', 'draft');

/**
 * Payload para crear el draft y para actualizarlo
 */
                $bookletId = $this->resolveBookletIdForLqi($contract);
                $tenant    = $this->resolveTenantClientSnapshot($contract); // client_id + snapshot

                if (!$tenant['client_id']) {
                    throw ValidationException::withMessages([
                        'client_id' => 'El contrato no tiene inquilino principal asignado.',
                    ]);
                }

                $payload = array_merge([
                    'booklet_id'                => $bookletId,
                    'voucher_type_short_name'   => 'LQI',                    // redundante pero explícito
                    'contract_id'               => $contract->id,
                    'issue_date'                => now()->toDateString(),    // irrelevante en draft
                    'period'                    => $from->toDateString(),    // convención: 1º del mes
                    'currency'                  => strtoupper($currency),
                    'generated_from_collection' => false,
                    'items'                     => [],                       // se setean abajo
                ], $tenant); // mergea client_id + snapshot


            // 2) Si no hay draft, crearlo vía VoucherService (NO instanciar Voucher directo)
            if (!$draft) {


                $draft = $this->voucherService->createFromArray($payload);
            }

            // 3) Cargos elegibles
            $eligible = $this->eligibleCharges($contract->id, $from, $to, $currency);
            // 4) Construir payload de items desde cargos elegibles
            $itemsPayload = $eligible->map(function (ContractCharge $charge) {
                $impact = $charge->chargeType?->tenant_impact;
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
            $dataPayload =  array_merge($payload, ['items'       => $itemsPayload]);


            \Log::info('- LqiBuilderService : UPDATE FROM ARRAY  -------------------------------');
            \Log::info('- LqiBuilderService : DATA PAYLOAD  -------------------------------');
            \Log::info('- LqiBuilderService : dataPayload', ['dataPayload' => $dataPayload]);

            // 5) Actualizar draft con items (idempotente) vía VoucherService
            $draft = $this->voucherService->updateFromArray($draft, $dataPayload);

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
                $q->whereIn('tenant_impact', ['add', 'subtract']);
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
        $type = $charge->chargeType?->name ?? 'Cargo';
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

        $clientId = $tenantContractClient?->client_id;
        $client   = $tenantContractClient?->client; // puede tener name, address, etc.

        return [
            'client_id'                => $clientId,
            'client_name'              => $client?->name,
            'client_address'           => $client?->address,
            'client_document_type_name'=> $client?->document_type_name,
            'client_document_number'   => $client?->document_number,
            'client_tax_condition_name'=> $client?->tax_condition_name,
            'client_tax_id_number'     => $client?->tax_id_number,
        ];
    }
}
