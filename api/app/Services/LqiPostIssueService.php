<?php

namespace App\Services;

use App\Enums\ChargeImpact;
use App\Enums\VoucherStatus;
use App\Models\Booklet;
use App\Models\ChargeType;
use App\Models\Contract;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\LqiOverviewService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LqiPostIssueService
{
    public function __construct(
        protected VoucherService $voucherService,
        protected LqiOverviewService $overviewService,
    ) {
    }

    public function issueAdjustments(int $contractId, string $period, string $currency): array
    {
        $normalizedCurrency = strtoupper(trim($currency));
        if ($normalizedCurrency === '') {
            throw ValidationException::withMessages([
                'currency' => 'La moneda es obligatoria.',
            ]);
        }

        $periodDate = $this->parsePeriod($period);
        $rowKey = $this->rowKey($contractId, $normalizedCurrency);
        $rowContext = $this->resolveRowContext($contractId, $periodDate->format('Y-m'), $normalizedCurrency);
        $status = $rowContext['status'] ?? 'none';

        if ($status !== 'issued') {
            return $this->buildInvalidStatusResponse($rowKey, $status, $rowContext);
        }

        /** @var Contract $contract */
        $contract = Contract::query()
            ->with(['mainTenant.client', 'collectionBooklet.voucherType', 'settlementBooklet.voucherType'])
            ->findOrFail($contractId);

        $blocked = $this->detectBlocks($contract, $periodDate);
        if (!empty($blocked)) {
            return [
                'row_key' => $rowKey,
                'result' => 'blocked',
                'reasons' => $blocked,
            ];
        }

        return DB::transaction(function () use ($contract, $periodDate, $normalizedCurrency, $rowKey) {
            $charges = $this->fetchPendingCharges($contract->id, $periodDate, $normalizedCurrency);
            $addCharges = $charges->filter(function (ContractCharge $charge) {
                return optional($charge->chargeType)->tenant_impact === ChargeImpact::ADD;
            })->values();
            $subtractCharges = $charges->filter(function (ContractCharge $charge) {
                return optional($charge->chargeType)->tenant_impact === ChargeImpact::SUBTRACT;
            })->values();

            if ($addCharges->isEmpty() && $subtractCharges->isEmpty()) {
                return [
                    'row_key' => $rowKey,
                    'result' => 'nothing_to_issue',
                    'notes' => [],
                    'nd' => $this->emptyNoteSummary(),
                    'nc' => $this->emptyNoteSummary(),
                ];
            }

            $lqiVoucher = $this->findIssuedLqi($contract->id, $periodDate, $normalizedCurrency);
            if ($addCharges->isNotEmpty() && !$lqiVoucher) {
                return [
                    'row_key' => $rowKey,
                    'result' => 'missing_lqi_for_adds',
                    'reasons' => ['missing_lqi_for_adds'],
                    'notes' => [],
                    'nd' => $this->pendingSummary($addCharges),
                    'nc' => $this->pendingSummary($subtractCharges),
                ];
            }

            $notes = [];
            $ndSummary = $this->emptyNoteSummary();
            $ncSummary = $this->emptyNoteSummary();

            if ($addCharges->isNotEmpty()) {
                $debitNote = $this->issueDebitNote($contract, $periodDate, $normalizedCurrency, $addCharges, $lqiVoucher);
                $ndSummary = $this->buildIssuedSummary($addCharges, $debitNote->id);
                $notes[] = $lqiVoucher ? 'issued_nd_associated_to_lqi' : 'issued_nd_standalone';
            }

            if ($subtractCharges->isNotEmpty()) {
                $creditNote = $this->issueCreditNote($contract, $periodDate, $normalizedCurrency, $subtractCharges, $lqiVoucher);
                $ncSummary = $this->buildIssuedSummary($subtractCharges, $creditNote->id);
                $notes[] = $lqiVoucher ? 'issued_nc_associated_to_lqi' : 'issued_nc_standalone';
            }

            return [
                'row_key' => $rowKey,
                'result' => 'ok',
                'notes' => array_values(array_unique($notes)),
                'nd' => $ndSummary,
                'nc' => $ncSummary,
            ];
        });
    }

    public function issueAdjustmentsBulk(string $period, array $filters = []): array
    {
        $rows = $this->collectRows($period, $filters);

        $processed = count($rows);
        $ndIssued = ['count' => 0, 'total' => 0.0];
        $ncIssued = ['count' => 0, 'total' => 0.0];
        $skipped = [];

        foreach ($rows as $row) {
            $contractId = (int) ($row['contract_id'] ?? 0);
            $currency = strtoupper((string) ($row['currency'] ?? ''));
            $rowKey = $row['row_key'] ?? $this->rowKey($contractId, $currency);
            $status = $row['status'] ?? null;

            if ($contractId <= 0 || $currency === '') {
                continue;
            }

            if ($status !== 'issued') {
                $skipped[] = $this->buildInvalidStatusSkipEntry($rowKey, $status, $row);
                continue;
            }

            $result = $this->issueAdjustments($contractId, $period, $currency);
            $resultCode = $result['result'] ?? 'unknown';

            if ($resultCode === 'ok') {
                if (!empty($result['nd']['issued'])) {
                    $ndIssued['count'] += (int) ($result['nd']['count'] ?? 0);
                    $ndIssued['total'] += (float) ($result['nd']['total'] ?? 0.0);
                }
                if (!empty($result['nc']['issued'])) {
                    $ncIssued['count'] += (int) ($result['nc']['count'] ?? 0);
                    $ncIssued['total'] += (float) ($result['nc']['total'] ?? 0.0);
                }
                continue;
            }

            if ($resultCode === 'blocked') {
                $reasons = $result['reasons'] ?? [];
                $skipped[] = [
                    'row_key' => $rowKey,
                    'reason' => 'blocked',
                    'reasons' => $reasons,
                ];
                continue;
            }

            if ($resultCode === 'missing_lqi_for_adds') {
                $skipped[] = [
                    'row_key' => $rowKey,
                    'reason' => 'lqi_missing_for_adds',
                    'reasons' => ['missing_lqi_for_adds'],
                    'add_pending_total' => (float) ($row['add_total'] ?? 0.0),
                    'subtract_pending_total' => (float) ($row['subtract_total'] ?? 0.0),
                    'suggestions' => [
                        ['action' => 'issue_lqi'],
                    ],
                ];
                continue;
            }

            if ($resultCode === 'nothing_to_issue') {
                $skipped[] = [
                    'row_key' => $rowKey,
                    'reason' => 'already_issued_no_pending',
                    'reasons' => ['nothing_to_issue'],
                ];
                continue;
            }

            $skipped[] = [
                'row_key' => $rowKey,
                'reason' => $resultCode,
                'reasons' => [$resultCode],
            ];
        }

        return [
            'processed' => $processed,
            'nd_issued' => $this->formatBulkSummary($ndIssued),
            'nc_issued' => $this->formatBulkSummary($ncIssued),
            'skipped' => $skipped,
        ];
    }

    private function parsePeriod(string $period): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'period' => 'Formato de período inválido. Usá YYYY-MM.',
            ]);
        }
    }

    private function rowKey(int $contractId, string $currency): string
    {
        return $contractId . '|' . strtoupper($currency);
    }

    private function detectBlocks(Contract $contract, Carbon $period): array
    {
        $reasons = [];

        if ($contract->adjustments()->blockingForPeriod($period)->exists()) {
            $reasons[] = 'pending_adjustment';
        }

        if (!$this->hasRentChargeInPeriod($contract->id, $period)) {
            $reasons[] = 'missing_rent';
        }

        return $reasons;
    }

    private function hasRentChargeInPeriod(int $contractId, Carbon $period): bool
    {
        $start = $period->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        return ContractCharge::query()
            ->where('contract_id', $contractId)
            ->whereDate('effective_date', '>=', $start->toDateString())
            ->whereDate('effective_date', '<', $end->toDateString())
            ->where('is_canceled', false)
            ->whereHas('chargeType', function ($query) {
                $query->where('code', ChargeType::CODE_RENT);
            })
            ->exists();
    }

    private function fetchPendingCharges(int $contractId, Carbon $period, string $currency): Collection
    {
        $start = $period->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        return ContractCharge::query()
            ->with('chargeType')
            ->where('contract_id', $contractId)
            ->whereDate('effective_date', '>=', $start->toDateString())
            ->whereDate('effective_date', '<', $end->toDateString())
            ->where('currency', $currency)
            ->where('is_canceled', false)
            ->whereNull('tenant_liquidation_settled_at')
            ->whereHas('chargeType', function ($query) {
                $query->whereIn('tenant_impact', ['add', 'subtract']);
            })
            ->lockForUpdate()
            ->get();
    }

    private function findIssuedLqi(int $contractId, Carbon $period, string $currency): ?Voucher
    {
        $voucherTypeId = $this->resolveVoucherTypeId('LQI');

        return Voucher::query()
            ->where('voucher_type_id', $voucherTypeId)
            ->where('contract_id', $contractId)
            ->whereDate('period', $period->toDateString())
            ->where('currency', $currency)
            ->where('status', VoucherStatus::Issued->value)
            ->lockForUpdate()
            ->first();
    }

    private function emptyNoteSummary(): array
    {
        return [
            'issued' => false,
            'count' => 0,
            'total' => 0.0,
            'voucher_id' => null,
        ];
    }

    private function pendingSummary(Collection $charges): array
    {
        if ($charges->isEmpty()) {
            return $this->emptyNoteSummary();
        }

        return [
            'issued' => false,
            'count' => $charges->count(),
            'total' => $this->formatAmount($charges->sum(fn (ContractCharge $charge) => (float) $charge->amount)),
            'voucher_id' => null,
        ];
    }

    private function buildIssuedSummary(Collection $charges, int $voucherId): array
    {
        return [
            'issued' => true,
            'count' => $charges->count(),
            'total' => $this->formatAmount($charges->sum(fn (ContractCharge $charge) => (float) $charge->amount)),
            'voucher_id' => $voucherId,
        ];
    }

    private function formatAmount(float $value): float
    {
        return round($value, 2);
    }

    private function issueDebitNote(
        Contract $contract,
        Carbon $period,
        string $currency,
        Collection $charges,
        ?Voucher $associatedVoucher
    ): Voucher {
        $payload = array_merge($this->buildBasePayload($contract, $period, $currency), [
            'booklet_id' => $this->resolveDebitNoteBookletId($contract),
            'voucher_type_short_name' => 'N/D',
            'items' => $charges->map(function (ContractCharge $charge) {
                return [
                    'type' => 'charge',
                    'description' => $this->buildChargeDescription($charge),
                    'quantity' => 1,
                    'unit_price' => $charge->amount,
                    'tax_rate_id' => null,
                    'impact' => 'add',
                    'contract_charge_id' => $charge->id,
                ];
            })->values()->all(),
            'meta' => [
                'lqi_debit_note' => true,
            ],
        ]);

        if ($associatedVoucher) {
            $payload['associated_voucher_ids'] = [(int) $associatedVoucher->id];
        }
        $voucher = $this->voucherService->createFromArray($payload);
        $issued = $this->voucherService->issue($voucher);

        $this->markChargesSettled($charges, $issued->id);

        return $issued;
    }

    private function issueCreditNote(
        Contract $contract,
        Carbon $period,
        string $currency,
        Collection $charges,
        ?Voucher $associatedVoucher
    ): Voucher {
        $payload = array_merge($this->buildBasePayload($contract, $period, $currency), [
            'booklet_id' => $this->resolveCreditNoteBookletId($contract),
            'voucher_type_short_name' => 'N/C',
            'items' => $charges->map(function (ContractCharge $charge) {
                return [
                    'type' => 'charge',
                    'description' => $this->buildChargeDescription($charge),
                    'quantity' => 1,
                    'unit_price' => $charge->amount,
                    'tax_rate_id' => null,
                    'impact' => 'subtract',
                    'contract_charge_id' => $charge->id,
                ];
            })->values()->all(),
            'meta' => [
                'lqi_credit_note' => true,
            ],
        ]);

        if ($associatedVoucher) {
            $payload['associated_voucher_ids'] = [(int) $associatedVoucher->id];
        }

        $voucher = $this->voucherService->createFromArray($payload);
        $issued = $this->voucherService->issue($voucher);

        $this->markChargesSettled($charges, $issued->id);

        return $issued;
    }

    private function buildBasePayload(Contract $contract, Carbon $period, string $currency): array
    {
        $snapshot = $this->resolveTenantSnapshot($contract);

        return array_merge($snapshot, [
            'contract_id' => $contract->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'period' => $period->toDateString(),
            'currency' => $currency,
            'generated_from_collection' => false,
        ]);
    }

    private function resolveTenantSnapshot(Contract $contract): array
    {
        $tenantContractClient = $contract->mainTenant()->with('client')->first();

        $client = $tenantContractClient?->client;

        return [
            'client_id' => $tenantContractClient?->client_id,
            'client_name' => $client?->name,
            'client_address' => $client?->address,
            'client_document_type_name' => $client?->document_type_name,
            'client_document_number' => $client?->document_number,
            'client_tax_condition_name' => $client?->tax_condition_name,
            'client_tax_id_number' => $client?->tax_id_number,
        ];
    }

    private function resolveDebitNoteBookletId(Contract $contract): int
    {
        $booklet = $contract->collectionBooklet?->voucherType?->short_name === 'N/D'
            ? $contract->collectionBooklet
            : null;

        if (!$booklet && $contract->settlementBooklet?->voucherType?->short_name === 'N/D') {
            $booklet = $contract->settlementBooklet;
        }

        return $booklet?->id ?? $this->resolveBookletIdByShortName('N/D');
    }

    private function resolveCreditNoteBookletId(Contract $contract): int
    {
        $booklet = $contract->collectionBooklet?->voucherType?->short_name === 'N/C'
            ? $contract->collectionBooklet
            : null;

        if (!$booklet && $contract->settlementBooklet?->voucherType?->short_name === 'N/C') {
            $booklet = $contract->settlementBooklet;
        }

        return $booklet?->id ?? $this->resolveBookletIdByShortName('N/C');
    }

    private function resolveBookletIdByShortName(string $short): int
    {
        $booklet = Booklet::query()
            ->whereHas('voucherType', function ($query) use ($short) {
                $query->where('short_name', $short);
            })
            ->orderBy('id')
            ->first();

        if (!$booklet) {
            throw ValidationException::withMessages([
                'booklet_id' => "No se encontró talonario configurado para comprobantes {$short}.",
            ]);
        }

        return $booklet->id;
    }

    private function buildChargeDescription(ContractCharge $charge): string
    {
        $type = $charge->chargeType?->name ?? 'Cargo';
        $ym = Carbon::parse($charge->effective_date)->format('Y-m');

        return $type . ' ' . $ym . ($charge->description ? ' – ' . $charge->description : '');
    }

    private function collectRows(string $period, array $filters): array
    {
        $normalizedPeriod = $this->parsePeriod($period)->format('Y-m');
        $currency = $this->normalizeCurrencyFilter($filters['currency'] ?? null);
        $status = $filters['state'] ?? $filters['status'] ?? null;
        $status = $status ? strtolower((string) $status) : null;
        if ($status === 'all') {
            $status = null;
        }
        $contractId = $filters['contract_id'] ?? null;
        $contractId = $contractId !== null ? (int) $contractId : null;

        $hasEligibles = $filters['has_eligibles'] ?? null;
        if ($hasEligibles !== null) {
            $hasEligibles = filter_var($hasEligibles, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $rows = [];
        $page = 1;
        $perPage = 200;

        do {
            $response = $this->overviewService->overview([
                'period' => $normalizedPeriod,
                'currency' => $currency,
                'contract_id' => $contractId,
                'status' => $status,
                'has_eligibles' => $hasEligibles,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $data = $response['data'] ?? [];
            if (empty($data)) {
                break;
            }

            $rows = array_merge($rows, $data);
            $total = (int) ($response['meta']['total'] ?? count($rows));
            $page++;
        } while (count($rows) < $total);

        return $rows;
    }

    private function resolveRowContext(int $contractId, string $periodKey, string $currency): array
    {
        $response = $this->overviewService->overview([
            'period' => $periodKey,
            'currency' => $currency,
            'contract_id' => $contractId,
            'per_page' => 200,
        ]);

        foreach ($response['data'] ?? [] as $row) {
            if (($row['row_key'] ?? null) === $this->rowKey($contractId, $currency)) {
                return $row;
            }
        }

        return [];
    }

    private function determineInvalidStatusMeta(?string $status, array $row): array
    {
        $normalizedStatus = $status ?? 'none';
        $addTotal = (float) ($row['add_total'] ?? 0.0);
        $subtractTotal = (float) ($row['subtract_total'] ?? 0.0);

        if ($normalizedStatus === 'none') {
            if ($addTotal > 0) {
                return [
                    'reasons' => ['lqi_required'],
                    'suggestions' => [
                        ['action' => 'issue_lqi'],
                    ],
                ];
            }

            if ($subtractTotal > 0) {
                return [
                    'reasons' => ['standalone_nc_available'],
                    'suggestions' => [
                        ['action' => 'issue_nc_alone'],
                    ],
                ];
            }

            return [
                'reasons' => ['lqi_required'],
                'suggestions' => [
                    ['action' => 'issue_lqi'],
                ],
            ];
        }

        if ($normalizedStatus === 'draft') {
            return [
                'reasons' => ['draft_requires_issue'],
                'suggestions' => [
                    ['action' => 'issue_lqi'],
                ],
            ];
        }

        return [
            'reasons' => ['unsupported_status'],
            'suggestions' => [],
        ];
    }

    private function buildInvalidStatusResponse(string $rowKey, ?string $status, array $row): array
    {
        $meta = $this->determineInvalidStatusMeta($status, $row);
        $normalizedStatus = $status ?? 'none';

        $response = [
            'row_key' => $rowKey,
            'result' => 'invalid_status_for_post_issue',
            'status' => $normalizedStatus,
        ];

        if (!empty($meta['reasons'])) {
            $response['reasons'] = array_values(array_unique($meta['reasons']));
        }
        if (!empty($meta['suggestions'])) {
            $response['suggestions'] = $meta['suggestions'];
        }

        return $response;
    }

    private function buildInvalidStatusSkipEntry(string $rowKey, ?string $status, array $row): array
    {
        $meta = $this->determineInvalidStatusMeta($status, $row);

        $normalizedStatus = $status ?? 'none';

        $entry = [
            'row_key' => $rowKey,
            'reason' => 'invalid_status_for_post_issue',
            'reasons' => array_values(array_unique($meta['reasons'] ?? [])),
            'status' => $normalizedStatus,
            'add_total' => (float) ($row['add_total'] ?? 0.0),
            'subtract_total' => (float) ($row['subtract_total'] ?? 0.0),
        ];

        if (!empty($meta['suggestions'])) {
            $entry['suggestions'] = $meta['suggestions'];
        }

        return $entry;
    }

    private function normalizeCurrencyFilter(?string $currency): ?string
    {
        if ($currency === null) {
            return null;
        }

        $value = strtoupper(trim((string) $currency));

        if ($value === '' || $value === 'ALL' || $value === 'TODAS') {
            return 'ALL';
        }

        return $value;
    }

    private function formatBulkSummary(array $summary): array
    {
        return [
            'count' => (int) ($summary['count'] ?? 0),
            'total' => round((float) ($summary['total'] ?? 0.0), 2),
        ];
    }

    private function markChargesSettled(Collection $charges, int $voucherId): void
    {
        if ($charges->isEmpty()) {
            return;
        }

        $now = now();
        ContractCharge::query()
            ->whereIn('id', $charges->pluck('id')->all())
            ->update([
                'tenant_liquidation_voucher_id' => $voucherId,
                'tenant_liquidation_settled_at' => $now,
            ]);
    }

    private function resolveVoucherTypeId(string $shortName): int
    {
        static $cache = [];
        $short = strtoupper($shortName);

        if (isset($cache[$short])) {
            return $cache[$short];
        }

        $id = VoucherType::query()
            ->where('short_name', $short)
            ->value('id');

        if (!$id) {
            throw ValidationException::withMessages([
                'voucher_type' => "No se encontró el tipo de comprobante {$short} configurado.",
            ]);
        }

        $cache[$short] = (int) $id;

        return $cache[$short];
    }
}
