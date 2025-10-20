<?php

namespace App\Services\Vouchers;

use App\Enums\ContractChargeStatus;
use App\Enums\VoucherItemType;
use App\Models\ChargeType;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\VoucherType;
use App\Services\VoucherCalculationService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherGenerationService
{
    public function __construct(private VoucherCalculationService $calculationService, private VoucherService $voucherService)
    {
    }

    public function generate(Carbon $period, ?array $contractIds, array $options): array
    {
        $createOrSync = $options['create_or_sync'] ?? 'sync';
        $requirePagInt = (bool) ($options['require_pag_int'] ?? true);
        $includeBonifications = (bool) ($options['include_bonifications'] ?? true);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $processed = $created = $synced = $skipped = 0;
        $blockedAdjustment = $missingRent = $agencyMissingPag = $errors = 0;
        $results = [];

        $contractsQuery = Contract::query()->activeDuring($period);
        if ($contractIds && is_array($contractIds) && count($contractIds) > 0) {
            $contractsQuery->whereIn('id', $contractIds);
        }

        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        $rentTypeId = ChargeType::where('code', 'RENT')->value('id');
        $recOtId = ChargeType::where('code', 'RECOVERY_OT')->value('id');
        $recAtId = ChargeType::where('code', 'RECOVERY_AT')->value('id');
        $bonusId = ChargeType::where('code', 'BONUS')->value('id');

        $voucherType = VoucherType::where('short_name', 'COB')->first();

        $contractsQuery->chunkById(200, function ($contracts) use (
            &$processed, &$created, &$synced, &$skipped, &$blockedAdjustment, &$missingRent, &$agencyMissingPag, &$errors, &$results,
            $period, $start, $end, $voucherType, $rentTypeId, $recOtId, $recAtId, $bonusId, $createOrSync, $requirePagInt, $includeBonifications, $dryRun
        ) {
            foreach ($contracts as $contract) {
                $processed++;
                $notes = [];

                try {
                    // Rules: blocks
                    $hasBlockedAdj = ContractAdjustment::query()
                        ->where('contract_id', $contract->id)
                        ->blockingForPeriod($period)
                        ->exists();
                    if ($hasBlockedAdj) {
                        $blockedAdjustment++;
                        $results[] = ['contract_id' => $contract->id, 'status' => 'blocked', 'voucher_id' => null, 'notes' => ['BLOCKED_ADJUSTMENT']];
                        continue;
                    }

                    $hasRent = $rentTypeId ? ContractCharge::query()
                        ->where('contract_id', $contract->id)
                        ->where('charge_type_id', $rentTypeId)
                        ->whereDate('effective_date', $start->toDateString())
                        ->exists() : false;
                    if (!$hasRent) {
                        $missingRent++;
                        $results[] = ['contract_id' => $contract->id, 'status' => 'blocked', 'voucher_id' => null, 'notes' => ['MISSING_RENT']];
                        continue;
                    }

                    // Determine charges to include
                    $typeIds = array_filter([$rentTypeId, $recOtId, $recAtId, $includeBonifications ? $bonusId : null]);
                    $charges = ContractCharge::query()
                        ->where('contract_id', $contract->id)
                        ->whereIn('charge_type_id', $typeIds)
                        ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                        ->whereIn('status', ['pending', 'validated'])
                        ->get();

                    if ($requirePagInt) {
                        $charges = $charges->filter(function (ContractCharge $c) use ($recAtId, &$notes, &$agencyMissingPag) {
                            if ($c->charge_type_id === $recAtId && !$c->is_paid) {
                                $notes[] = 'AGENCY_MISSING_PAG';
                                $agencyMissingPag++;
                                return false;
                            }
                            return true;
                        });
                    }

                    if ($dryRun || $charges->isEmpty()) {
                        $skipped++;
                        $results[] = ['contract_id' => $contract->id, 'status' => 'skipped', 'voucher_id' => null, 'notes' => $notes];
                        continue;
                    }

                    // Group by currency for multi-currency
                    $byCurrency = $charges->groupBy('currency');

                    foreach ($byCurrency as $currency => $group) {
                        DB::transaction(function () use (
                            $contract, $currency, $period, $group, $voucherType, $createOrSync, &$created, &$synced, &$results, &$notes
                        ) {
                            // Validar booklet del contrato
                            $bookletId = $contract->collection_booklet_id;
                            if (!$bookletId) {
                                throw new \RuntimeException('El contrato no tiene talonario de cobranzas (collection_booklet_id) configurado');
                            }

                            // Datos del cliente
                            $tenant = $contract->mainTenant()->with('client')->first();
                            $clientId = $tenant?->client_id;
                            $client = $tenant?->client;
                            if (!$clientId) {
                                throw new \RuntimeException('Contrato sin inquilino principal asignado');
                            }

                            $baseData = [
                                'generated_from_collection' => true,
                                'voucher_type_short_name' => 'COB',
                                'booklet_id' => $bookletId,
                                'contract_id' => $contract->id,
                                'client_id' => $clientId,
                                'client_name' => $client?->full_name ?? $client?->name ?? ('Cliente #' . $clientId),
                                'client_address' => $client?->address ?? null,
                                'client_document_type_name' => $client?->document_type_name ?? null,
                                'client_document_number' => $client?->document_number ?? null,
                                'client_tax_condition_name' => $client?->tax_condition_name ?? null,
                                'client_tax_id_number' => $client?->tax_id_number ?? null,
                                'currency' => $currency,
                                'period' => $period->copy()->startOfMonth()->toDateString(),
                                'issue_date' => now()->toDateString(),
                                'due_date' => now()->endOfMonth()->toDateString(),
                                'items' => $group->map(function (ContractCharge $charge) {
                                    $type = $charge->chargeType?->code === 'RENT' ? 'rent'
                                        : ($charge->chargeType?->code === 'BONUS' ? 'discount' : 'charge');
                                    return [
                                        'type' => $type,
                                        'description' => $charge->description ?? ($charge->chargeType?->name ?? 'Cargo'),
                                        'quantity' => 1,
                                        'unit_price' => $charge->amount,
                                        'contract_charge_id' => $charge->id,
                                    ];
                                })->values()->all(),
                            ];

                            // Buscar draft existente si corresponde
                            $existing = null;
                            if ($createOrSync === 'sync') {
                                $existing = Voucher::query()
                                    ->where('contract_id', $contract->id)
                                    ->whereDate('period', $period->copy()->startOfMonth()->toDateString())
                                    ->where('currency', $currency)
                                    ->where('status', VoucherStatus::Draft->value)
                                    ->where('voucher_type_short_name', 'COB')
                                    ->first();
                            }

                            if ($existing) {
                                $this->voucherService->updateFromArray($existing, $baseData);
                                $voucherId = $existing->id;
                                $synced++;
                                $status = 'synced';
                            } else {
                                $voucher = $this->voucherService->createFromArray($baseData);
                                $voucherId = $voucher->id;
                                $created++;
                                $status = 'created';
                            }

                            // Marcar charges como incluidas
                            foreach ($group as $charge) {
                                $charge->included_in_voucher = true;
                                $charge->voucher_id = $voucherId;
                                if ($charge->status === ContractChargeStatus::PENDING) {
                                    $charge->status = ContractChargeStatus::BILLED; // o INCLUDED según convención
                                }
                                $charge->save();
                            }

                            $results[] = [
                                'contract_id' => $contract->id,
                                'status' => $status,
                                'voucher_id' => $voucherId,
                                'notes' => $notes,
                            ];
                        });
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $results[] = [
                        'contract_id' => $contract->id,
                        'status' => 'error',
                        'voucher_id' => null,
                        'notes' => [$e->getMessage()],
                    ];
                }
            }
        });

        return [
            'summary' => [
                'processed' => $processed,
                'created' => $created,
                'synced' => $synced,
                'skipped' => $skipped,
                'blocked_adjustment' => $blockedAdjustment,
                'missing_rent' => $missingRent,
                'agency_missing_pag' => $agencyMissingPag,
                'errors' => $errors,
            ],
            'results' => $results,
        ];
    }
}
