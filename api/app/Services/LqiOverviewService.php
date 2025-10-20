<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Models\Booklet;
use App\Models\ChargeType;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Models\VoucherType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LqiOverviewService
{
    protected LqiBuilderService $builder;
    protected VoucherService $voucherService;

    public function __construct(
        LqiBuilderService $builder,
        VoucherService $voucherService
    ) {
        $this->builder = $builder;
        $this->voucherService = $voucherService;
    }

    public function overview(array $filters): array
    {
        $context = $this->buildContext($filters);

        $page    = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 25));
        $offset  = ($page - 1) * $perPage;

        $rows   = array_values($context['rows']);
        $total  = count($rows);
        $slice  = array_slice($rows, $offset, $perPage);

        return [
            'data' => $slice,
            'meta' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
            ],
            'available_currencies' => array_values($context['currencies']),
        ];
    }

    public function kpis(array $filters): array
    {
        $context = $this->buildContext($filters);
        return $context['kpis'];
    }

    public function generate(array $filters): array
    {
        $context  = $this->buildContext($filters, true);
        $rows     = array_values($context['rows']);
        $period   = $context['period'];
        $periodKey = $period->format('Y-m');

        $createdOrUpdated = 0;
        $creditNotesSuggested = 0;
        $creditNotesOnly = 0;
        $skipped = [];

        /** @var Collection<int, Contract> $contracts */
        $contracts = $context['contracts'];

        Log::info('LqiOverviewService.generate.start', [
            'filters' => $filters,
            'rows_total' => count($rows),
        ]);

        foreach ($rows as $row) {
            Log::debug('LqiOverviewService.generate.row', [
                'row_key' => $row['row_key'] ?? null,
                'status' => $row['status'] ?? null,
                'eligible_count' => $row['eligible_count'] ?? null,
                'currency' => $row['currency'] ?? null,
                'has_voucher' => $row['has_voucher'] ?? null,
            ]);

            $blockedReasons = $row['blocked_reasons'] ?? [];
            if (!empty($blockedReasons)) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => $blockedReasons[0] ?? 'blocked',
                    'reasons' => $blockedReasons,
                ];
                continue;
            }

            $blockedReasons = $row['blocked_reasons'] ?? [];
            if (!empty($blockedReasons)) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => $blockedReasons[0] ?? 'blocked',
                    'reasons' => $blockedReasons,
                ];
                continue;
            }

            $hasAdd = ($row['add_count'] ?? 0) > 0;
            $hasSubtract = ($row['subtract_count'] ?? 0) > 0;

            if ($row['status'] === 'issued' && $hasAdd) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'already_issued',
                    'reasons' => ['already_issued'],
                ];
                continue;
            }

            if (!$hasAdd) {
                if ($hasSubtract) {
                    $creditNotesOnly++;
                    $skipped[] = [
                        'row_key' => $row['row_key'],
                        'reason'  => 'credit_only',
                        'reasons' => ['credit_only'],
                    ];
                } else {
                    $skipped[] = [
                        'row_key' => $row['row_key'],
                        'reason'  => 'no_eligibles',
                        'reasons' => ['no_eligibles'],
                    ];
                }
                continue;
            }

            if ($row['status'] === 'issued') {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'already_issued',
                    'reasons' => ['already_issued'],
                ];
                continue;
            }

            if (($row['add_total'] ?? 0) <= 0) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'no_eligibles',
                    'reasons' => ['no_eligibles'],
                ];
                continue;
            }

            $contract = $contracts->get($row['contract_id']);
            if (!$contract) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'contract_not_found',
                    'reasons' => ['contract_not_found'],
                ];
                continue;
            }

            try {
                if ($hasSubtract) {
                    $creditNotesSuggested++;
                }
                $this->builder->sync($contract, $periodKey, $row['currency']);
                $createdOrUpdated++;
            } catch (\Throwable $e) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'exception',
                    'message' => $e->getMessage(),
                    'reasons' => ['exception'],
                ];
            }
        }

        Log::info('LqiOverviewService.generate.finish', [
            'updated' => $createdOrUpdated,
            'skipped' => count($skipped),
        ]);

        return [
            'updated' => $createdOrUpdated,
            'credit_suggested' => $creditNotesSuggested,
            'credit_only' => $creditNotesOnly,
            'skipped' => $skipped,
        ];
    }

    public function issue(array $filters): array
    {
        $context = $this->buildContext($filters, true);
        $rows    = array_values($context['rows']);

        /** @var Collection<int, Contract> $contracts */
        $contracts = $context['contracts'];
        $period = $context['period'];

        $issuedLqi  = 0;
        $issuedCreditAssociated = 0;
        $issuedCreditOnly = 0;
        $skipped = [];

        foreach ($rows as $row) {
            $blockedReasons = $row['blocked_reasons'] ?? [];
            if (!empty($blockedReasons)) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => $blockedReasons[0] ?? 'blocked',
                    'reasons' => $blockedReasons,
                ];
                continue;
            }

            $hasAdd = ($row['add_count'] ?? 0) > 0;
            $hasSubtract = ($row['subtract_count'] ?? 0) > 0;

            $contract = $contracts->get($row['contract_id']);
            if (!$contract) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'contract_not_found',
                    'reasons' => ['contract_not_found'],
                ];
                continue;
            }

            // Caso NC sola (solo subtract)
            if (!$hasAdd && $hasSubtract) {
                try {
                    $creditIssued = $this->issueCreditNote($contract, $period, (string) $row['currency'], null);
                    if ($creditIssued) {
                        $issuedCreditOnly++;
                    } else {
                        $skipped[] = [
                            'row_key' => $row['row_key'],
                            'reason'  => 'credit_empty',
                            'reasons' => ['credit_empty'],
                        ];
                    }
                } catch (\Throwable $e) {
                    $skipped[] = [
                        'row_key' => $row['row_key'],
                        'reason'  => 'exception',
                        'message' => $e->getMessage(),
                        'reasons' => ['exception'],
                    ];
                }
                continue;
            }

            if (!$hasAdd) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'no_eligibles',
                    'reasons' => ['no_eligibles'],
                ];
                continue;
            }

            if (($row['status'] ?? null) !== 'draft') {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'not_draft',
                    'reasons' => ['not_draft'],
                ];
                continue;
            }

            if (($row['add_total'] ?? 0) <= 0 || ($row['voucher_items_count'] ?? 0) === 0) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'total_zero',
                    'reasons' => ['total_zero'],
                ];
                continue;
            }

            if (!$row['voucher_id']) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'missing_voucher',
                    'reasons' => ['missing_voucher'],
                ];
                continue;
            }

            $voucher = Voucher::query()
                ->with(['booklet.voucherType'])
                ->find($row['voucher_id']);

            if (!$voucher) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'missing_voucher',
                    'reasons' => ['missing_voucher'],
                ];
                continue;
            }

            try {
                $this->voucherService->issue($voucher);
                $issuedLqi++;

                if ($hasSubtract) {
                    $creditIssued = $this->issueCreditNote($contract, $period, (string) $row['currency'], $voucher);
                    if ($creditIssued) {
                        $issuedCreditAssociated++;
                    }
                }
            } catch (\Throwable $e) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'exception',
                    'message' => $e->getMessage(),
                    'reasons' => ['exception'],
                ];
            }
        }

        return [
            'issued'  => $issuedLqi,
            'credit_issued' => $issuedCreditAssociated,
            'credit_only_issued' => $issuedCreditOnly,
            'skipped' => $skipped,
        ];
    }

    public function reopen(array $filters, ?string $reason = null): array
    {
        $context = $this->buildContext($filters, true);
        $rows    = array_values($context['rows']);

        $success = 0;
        $skipped = [];

        $reason = $reason ? trim($reason) : 'Reapertura masiva';

        foreach ($rows as $row) {
            if (($row['status'] ?? null) !== 'issued') {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'not_issued',
                    'reasons' => ['not_issued'],
                ];
                continue;
            }

            if ($row['voucher_has_collections'] ?? false) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'has_collections',
                    'reasons' => ['has_collections'],
                ];
                continue;
            }

            if (!$row['voucher_id']) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'missing_voucher',
                    'reasons' => ['missing_voucher'],
                ];
                continue;
            }

            $voucher = Voucher::query()
                ->with(['booklet.voucherType'])
                ->find($row['voucher_id']);

            if (!$voucher) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'missing_voucher',
                    'reasons' => ['missing_voucher'],
                ];
                continue;
            }

            try {
                $this->voucherService->reopenLqi($voucher, $reason);
                $success++;
            } catch (\Throwable $e) {
                $skipped[] = [
                    'row_key' => $row['row_key'],
                    'reason'  => 'exception',
                    'message' => $e->getMessage(),
                    'reasons' => ['exception'],
                ];
            }
        }

        return [
            'reopened' => $success,
            'skipped'  => $skipped,
        ];
    }

    private function buildContext(array $filters, bool $eagerContracts = false, bool $applyFilters = true): array
    {
        \Log::info('LqiOverviewService.buildContext.start - ' . microtime(true));
        \Log::info('LqiOverviewService.buildContext', [
            'filters' => $filters,
            'eagerContracts' => $eagerContracts,
            'applyFilters' => $applyFilters,
        ]);

        $periodString = $filters['period'] ?? null;
        if (!$periodString) {
            throw ValidationException::withMessages([
                'period' => 'El período es obligatorio (formato YYYY-MM).',
            ]);
        }

        $period = $this->parsePeriod($periodString);
        $currencyFilter = $this->normalizeCurrency($filters['currency'] ?? null);
        $contractId     = $filters['contract_id'] ?? null;
        $statusFilter   = $this->normalizeStatus($filters['status'] ?? null);
        $hasEligiblesFilter = null;
        if (array_key_exists('has_eligibles', $filters)) {
            $rawHasEligibles = $filters['has_eligibles'];
            if ($rawHasEligibles !== null && $rawHasEligibles !== '') {
                $hasEligiblesFilter = filter_var($rawHasEligibles, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }

        $contracts = $this->fetchContracts($period, $contractId);
        $contractMap = $contracts->keyBy('id');

        $contractIds = $contractMap->keys()->all();
        if (empty($contractIds)) {
            return [
                'rows'       => [],
                'currencies' => [],
                'contracts'  => collect(),
                'period'     => $period,
                'kpis'       => $this->emptyKpis(),
            ];
        }

        $charges = $this->fetchCharges($contractIds, $period, $currencyFilter);
        $voucherTypeId = $this->resolveVoucherTypeId();
        $vouchers = $this->fetchVouchers($contractIds, $period, $currencyFilter, $voucherTypeId);
        $creditNotes = $this->fetchCreditNotes($contractIds, $period, $currencyFilter);
        $periodUniverseCharges = $this->fetchPeriodCharges($contractIds, $period, $currencyFilter);

        $pendingAdjustments = $this->detectPendingAdjustments($contractIds, $period);
        $missingRent = $this->detectMissingRent($contractIds, $period, $contracts);
        $requireRentBeforeAny = (bool) config('features.lqi.require_rent_before_any_lqi', false);
        
        Log::info('LqiOverviewService.buildContext.datasets', [
            'contracts' => count($contractIds),
            'charges_rows' => $charges->count(),
            'vouchers_rows' => $vouchers->count(),
            'currency_filter' => $currencyFilter,
            'status_filter' => $statusFilter,
            'has_eligibles_filter' => $hasEligiblesFilter,
            'apply_filters' => $applyFilters,
        ]);

        // ensure we have contract models for any voucher contract not already loaded
        $extraContractIds = $vouchers->pluck('contract_id')
            ->unique()
            ->reject(function ($id) use ($contractMap) {
                return $contractMap->has($id);
            })
            ->values();

        if ($extraContractIds->isNotEmpty()) {
            $extraContracts = Contract::query()
                ->with(['property', 'mainTenant.client'])
                ->whereIn('id', $extraContractIds)
                ->get();
            $contracts = $contracts->merge($extraContracts);
            $contractMap = $contracts->keyBy('id');
        }

        $rows = [];
        $currencies = [];
        $alertsSummary = [
            'missing_tenant'      => [],
            'currency_mismatch'   => [],
        ];

        $chargeCurrenciesByContract = [];
        $voucherCurrenciesByContract = [];

        foreach ($contractMap as $contract) {
            if (!$contract->mainTenant || !$contract->mainTenant->client) {
                $alertsSummary['missing_tenant'][$contract->id] = true;
            }
        }
        \Log::info('LqiOverviewService.buildContext.charges', [
            'charges' => $charges,
        ]);
        $eligibilityPairs = [];
        $missingRentContracts = [];
        foreach ($missingRent as $id => $rentInfo) {
            if ($this->isContractMissingRent($rentInfo, $requireRentBeforeAny)) {
                $missingRentContracts[(int) $id] = true;
            }
        }

        $blockedContractIds = array_values(array_unique(array_merge(
            array_map('intval', array_keys($pendingAdjustments)),
            array_map('intval', array_keys($missingRentContracts))
        )));

        $issuanceUniverse = [
            'pairs' => [],
            'per_currency' => [],
            'overall' => ['count' => 0, 'total' => 0.0],
        ];

        $issuanceIssued = [
            'pairs' => [],
            'per_currency' => [],
            'overall' => ['count' => 0],
        ];

        foreach ($charges as $row) {
            $currency = $this->normalizeCurrency($row->currency ?? null);
            $contractIdRow = (int) $row->contract_id;
            $key = $this->rowKey($contractIdRow, $currency);

            $contract = $contractMap->get($contractIdRow);
            $rows[$key] = $rows[$key] ?? $this->makeBaseRow($contract, $currency);

            $addCount = (int) ($row->add_count ?? 0);
            $subtractCount = (int) ($row->subtract_count ?? 0);

            $rows[$key]['add_count'] = $addCount;
            $rows[$key]['add_total'] = (float) ($row->add_total ?? 0.0);
            $rows[$key]['add_total_abs'] = (float) ($row->add_total_abs ?? 0.0);
            $rows[$key]['add_pending_total'] = $rows[$key]['add_total'];
            $rows[$key]['subtract_count'] = $subtractCount;
            $rows[$key]['subtract_total'] = (float) ($row->subtract_total ?? 0.0);
            $rows[$key]['subtract_total_abs'] = (float) ($row->subtract_total_abs ?? 0.0);
            $rows[$key]['subtract_pending_total'] = $rows[$key]['subtract_total'];
            $rows[$key]['net_total'] = (float) ($row->net_total ?? 0.0);
            $rows[$key]['eligible_total'] = $rows[$key]['net_total'];
            $rows[$key]['eligible_total_abs'] = $rows[$key]['add_total_abs'] + $rows[$key]['subtract_total_abs'];
            $rows[$key]['eligible_count'] = $addCount + $subtractCount;
            $rows[$key]['has_eligibles'] = $rows[$key]['eligible_count'] > 0;
            $rows[$key]['has_positive'] = $addCount > 0;
            $rows[$key]['has_negative'] = $subtractCount > 0;
            $rows[$key]['nc_suggested'] = $subtractCount > 0;
            $rows[$key]['only_credit'] = ($addCount === 0 && $subtractCount > 0);
            $rows[$key]['only_credits'] = $rows[$key]['only_credit'];
            $rows[$key]['can_sync'] = $addCount > 0;

            $this->applyContractBlocks($rows[$key], $contractIdRow, $pendingAdjustments, $missingRent, $requireRentBeforeAny);

            $currencies[$currency] = $currency;
            $chargeCurrenciesByContract[$contractIdRow][$currency] = true;

            if ($contract && $this->contractCurrencyMismatch($contract, $currency)) {
                $alertsSummary['currency_mismatch'][$contract->id] = true;
                $rows[$key]['alerts'][] = 'currency_mismatch';
            }
        }

        foreach ($vouchers as $voucher) {
            $currency = $this->normalizeCurrency($voucher->currency ?? null);
            $contractIdRow = (int) $voucher->contract_id;
            $key = $this->rowKey($contractIdRow, $currency);

            $contract = $contractMap->get($contractIdRow);
            $rows[$key] = $rows[$key] ?? $this->makeBaseRow($contract, $currency);

            $statusEnum = $this->normalizeVoucherStatus($voucher->status);
            $statusValue = $statusEnum?->value ?? (is_string($voucher->status)
                ? ($voucher->status === 'canceled' ? VoucherStatus::Cancelled->value : $voucher->status)
                : null);

            $rows[$key]['status'] = $statusValue;
            $rows[$key]['voucher_id'] = $voucher->id;
            $rows[$key]['voucher_total'] = (float) $voucher->total;
            $rows[$key]['lqi_total'] = (float) $voucher->total;
            $rows[$key]['voucher_issue_date'] = optional($voucher->issue_date)->toDateString();
            $rows[$key]['voucher_items_count'] = (int) $voucher->items_count;
            $rows[$key]['voucher_has_collections'] = ($voucher->applications_count > 0) || ($voucher->payments_count > 0);
            $rows[$key]['has_voucher'] = true;
            $rows[$key]['can_sync'] = $statusEnum !== VoucherStatus::Issued;
            $rows[$key]['can_issue'] = $statusEnum === VoucherStatus::Draft && $voucher->items_count > 0 && abs((float) $voucher->total) >= 0.01;
            $rows[$key]['can_reopen'] = $statusEnum === VoucherStatus::Issued && !$rows[$key]['voucher_has_collections'];

            $this->applyContractBlocks($rows[$key], $contractIdRow, $pendingAdjustments, $missingRent, $requireRentBeforeAny);

            $currencies[$currency] = $currency;
            $voucherCurrenciesByContract[$contractIdRow][$currency] = true;

            if ($contract && $this->contractCurrencyMismatch($contract, $currency)) {
                $alertsSummary['currency_mismatch'][$contract->id] = true;
                if (!in_array('currency_mismatch', $rows[$key]['alerts'], true)) {
                    $rows[$key]['alerts'][] = 'currency_mismatch';
                }
            }
        }

        foreach ($creditNotes as $creditNote) {
            $currency = $this->normalizeCurrency($creditNote->currency ?? null);
            $contractIdRow = (int) $creditNote->contract_id;
            $key = $this->rowKey($contractIdRow, $currency);

            $contract = $contractMap->get($contractIdRow);
            $rows[$key] = $rows[$key] ?? $this->makeBaseRow($contract, $currency);

            $creditStatusEnum = $this->normalizeVoucherStatus($creditNote->status);
            $creditStatusValue = $creditStatusEnum?->value ?? (is_string($creditNote->status)
                ? ($creditNote->status === 'canceled' ? VoucherStatus::Cancelled->value : $creditNote->status)
                : null);

            $rows[$key]['credit_note_id'] = $creditNote->id;
            $rows[$key]['credit_note_status'] = $creditStatusValue;
            $rows[$key]['credit_note_total'] = (float) $creditNote->total;
            $rows[$key]['credit_note_issue_date'] = optional($creditNote->issue_date)->toDateString();
            $rows[$key]['credit_note_items_count'] = (int) $creditNote->items_count;

            $currencies[$currency] = $currency;
            $chargeCurrenciesByContract[$contractIdRow][$currency] = true;

            if ($contract && $this->contractCurrencyMismatch($contract, $currency)) {
                $alertsSummary['currency_mismatch'][$contract->id] = true;
                if (!in_array('currency_mismatch', $rows[$key]['alerts'], true)) {
                    $rows[$key]['alerts'][] = 'currency_mismatch';
                }
            }
        }

        foreach ($periodUniverseCharges as $row) {
            $contractIdRow = (int) $row->contract_id;
            $currency = $this->normalizeCurrency($row->currency ?? null);
            $key = $this->rowKey($contractIdRow, $currency);

            $addTotal = (float) ($row->add_total ?? 0.0);
            $addCount = (int) ($row->add_count ?? 0);

            if ($addTotal <= 0 || $addCount <= 0) {
                continue;
            }

            $blockedPending = isset($pendingAdjustments[$contractIdRow]);
            $rentInfoForRow = $missingRent[$contractIdRow] ?? [];
            $blockedMissing = $this->isRowMissingRent($rentInfoForRow, $currency, $requireRentBeforeAny);
            $isBlocked = $blockedPending || $blockedMissing;

            if (!isset($eligibilityPairs[$key])) {
                $eligibilityPairs[$key] = [
                    'contract_id' => $contractIdRow,
                    'currency' => $currency,
                    'add_total' => 0.0,
                    'blocked_pending' => $blockedPending,
                    'blocked_missing' => $blockedMissing,
                ];
            }

            $eligibilityPairs[$key]['add_total'] += $addTotal;
            $eligibilityPairs[$key]['blocked_pending'] = $eligibilityPairs[$key]['blocked_pending'] || $blockedPending;
            $eligibilityPairs[$key]['blocked_missing'] = $eligibilityPairs[$key]['blocked_missing'] || $blockedMissing;

            if ($isBlocked) {
                continue;
            }

            if (!isset($issuanceUniverse['pairs'][$key])) {
                $issuanceUniverse['pairs'][$key] = [
                    'contract_id' => $contractIdRow,
                    'currency' => $currency,
                    'add_total' => 0.0,
                ];
            }

            $issuanceUniverse['pairs'][$key]['add_total'] += $addTotal;

            if (!isset($issuanceUniverse['per_currency'][$currency])) {
                $issuanceUniverse['per_currency'][$currency] = ['count' => 0, 'total' => 0.0];
            }

            $issuanceUniverse['per_currency'][$currency]['count']++;
            $issuanceUniverse['per_currency'][$currency]['total'] += $addTotal;
            $issuanceUniverse['overall']['count']++;
            $issuanceUniverse['overall']['total'] += $addTotal;
        }

        foreach ($vouchers as $voucher) {
            if ($this->normalizeVoucherStatus($voucher->status) !== VoucherStatus::Issued) {
                continue;
            }

            $contractIdRow = (int) $voucher->contract_id;
            $currency = $this->normalizeCurrency($voucher->currency ?? null);
            $key = $this->rowKey($contractIdRow, $currency);

            if (isset($issuanceIssued['pairs'][$key])) {
                continue;
            }

            $issuanceIssued['pairs'][$key] = [
                'contract_id' => $contractIdRow,
                'currency' => $currency,
            ];

            if (!isset($issuanceIssued['per_currency'][$currency])) {
                $issuanceIssued['per_currency'][$currency] = ['count' => 0];
            }

            $issuanceIssued['per_currency'][$currency]['count']++;
            $issuanceIssued['overall']['count']++;
        }

        $creditSummary = [
            'per_currency' => [],
            'overall' => [
                'issued_count' => 0,
                'issued_total' => 0.0,
                'associated_count' => 0,
                'associated_total' => 0.0,
                'standalone_count' => 0,
                'standalone_total' => 0.0,
            ],
        ];

        foreach ($creditNotes as $creditNote) {
            if ($this->normalizeVoucherStatus($creditNote->status) !== VoucherStatus::Issued) {
                continue;
            }

            $currency = $this->normalizeCurrency($creditNote->currency ?? null);
            $amount = abs((float) ($creditNote->total ?? 0.0));
            $associated = (($creditNote->associations_count ?? 0) > 0);

            if (!isset($creditSummary['per_currency'][$currency])) {
                $creditSummary['per_currency'][$currency] = [
                    'issued_count' => 0,
                    'issued_total' => 0.0,
                    'associated_count' => 0,
                    'associated_total' => 0.0,
                    'standalone_count' => 0,
                    'standalone_total' => 0.0,
                ];
            }

            $creditSummary['per_currency'][$currency]['issued_count']++;
            $creditSummary['per_currency'][$currency]['issued_total'] += $amount;
            $creditSummary['overall']['issued_count']++;
            $creditSummary['overall']['issued_total'] += $amount;

            if ($associated) {
                $creditSummary['per_currency'][$currency]['associated_count']++;
                $creditSummary['per_currency'][$currency]['associated_total'] += $amount;
                $creditSummary['overall']['associated_count']++;
                $creditSummary['overall']['associated_total'] += $amount;
            } else {
                $creditSummary['per_currency'][$currency]['standalone_count']++;
                $creditSummary['per_currency'][$currency]['standalone_total'] += $amount;
                $creditSummary['overall']['standalone_count']++;
                $creditSummary['overall']['standalone_total'] += $amount;
            }
        }

        $chargesCoverageStats = $this->buildChargesCoverageStats(
            $contractIds,
            $period,
            $currencyFilter,
            $blockedContractIds
        );

        $autoCurrency = !$currencyFilter || $currencyFilter === 'ALL';

        foreach ($contractMap as $contract) {
            $contractIdValue = (int) $contract->id;
            $targetCurrencies = $this->resolveContractCurrencies(
                $contract,
                $currencyFilter,
                $chargeCurrenciesByContract[$contractIdValue] ?? [],
                $voucherCurrenciesByContract[$contractIdValue] ?? []
            );

            foreach ($targetCurrencies as $targetCurrency) {
                $key = $this->rowKey($contractIdValue, $targetCurrency);
                if (!isset($rows[$key])) {
                    $rows[$key] = $this->makeBaseRow($contract, $targetCurrency);
                    $this->applyContractBlocks($rows[$key], $contractIdValue, $pendingAdjustments, $missingRent, $requireRentBeforeAny);
                }
                if ($autoCurrency) {
                    $currencies[$targetCurrency] = $targetCurrency;
                }
            }
        }

        foreach ($rows as $key => $row) {
            $normalizedAlerts = array_values(array_unique($row['alerts'] ?? []));
            $normalizedReasons = array_values(array_unique($row['blocked_reasons'] ?? []));
            $row['alerts'] = $normalizedAlerts;
            $row['blocked_reasons'] = $normalizedReasons;
            $row['blocked'] = !empty($normalizedReasons);
            $row['eligible_count'] = (int) ($row['eligible_count'] ?? 0);
            $row['add_count'] = (int) ($row['add_count'] ?? 0);
            $row['add_total'] = (float) ($row['add_total'] ?? 0.0);
            $row['subtract_count'] = (int) ($row['subtract_count'] ?? 0);
            $row['subtract_total'] = (float) ($row['subtract_total'] ?? 0.0);
            $row['add_pending_total'] = (float) ($row['add_pending_total'] ?? $row['add_total']);
            $row['subtract_pending_total'] = (float) ($row['subtract_pending_total'] ?? $row['subtract_total']);
            $row['net_total'] = (float) ($row['net_total'] ?? 0.0);
            $row['has_positive'] = (bool) ($row['has_positive'] ?? ($row['add_count'] > 0));
            $row['has_negative'] = (bool) ($row['has_negative'] ?? ($row['subtract_count'] > 0));
            $row['has_eligibles'] = $row['has_positive'] || $row['has_negative'];
            $row['nc_suggested'] = $row['has_negative'];
            $row['only_credit'] = $row['has_negative'] && !$row['has_positive'];
            $row['can_sync'] = !$row['blocked']
                && ($row['status'] ?? 'none') !== 'issued'
                && $row['has_positive'];

            $rows[$key] = $row;
        }

        if ($applyFilters) {
            if (!is_null($statusFilter)) {
                $rows = array_filter($rows, function (array $row) use ($statusFilter) {
                    return ($row['status'] ?? 'none') === $statusFilter;
                });
            }

            if (!is_null($hasEligiblesFilter)) {
                $rows = array_filter($rows, function (array $row) use ($hasEligiblesFilter) {
                    $has = ($row['has_eligibles'] ?? false) === true;
                    return $hasEligiblesFilter ? $has : !$has;
                });
            }
        }

        $rows = array_values($rows);

        // Sort rows by contract + currency
        usort($rows, function (array $a, array $b) {
            $contractCmp = ($a['contract_id'] <=> $b['contract_id']);
            if ($contractCmp !== 0) {
                return $contractCmp;
            }
            return strcmp($a['currency'], $b['currency']);
        });

        $rowsAssoc = [];
        foreach ($rows as $row) {
            $rowsAssoc[$row['row_key']] = $row;
        }

        if ($autoCurrency) {
            $currencySet = [];
            foreach ($rowsAssoc as $row) {
                $currencySet[$row['currency']] = $row['currency'];
            }
            $currencies = $currencySet;
        } elseif ($currencyFilter) {
            $currencies = [$currencyFilter => $currencyFilter];
        }

        $kpis = $this->buildKpis(
            $rowsAssoc,
            $contractMap,
            $alertsSummary,
            $eligibilityPairs,
            $issuanceUniverse,
            $issuanceIssued,
            $creditSummary,
            $chargesCoverageStats
        );

        return [
            'rows'       => $rowsAssoc,
            'currencies' => array_values($currencies),
            'contracts'  => $contractMap,
            'period'     => $period,
            'kpis'       => $kpis,
        ];
    }

    private function parsePeriod(string $period): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'period' => 'Formato de período inválido. Usá YYYY-MM.',
            ]);
        }
    }

    private function normalizeCurrency(?string $currency): ?string
    {
        if (!$currency) {
            return null;
        }
        $value = strtoupper(trim($currency));
        return $value === '' ? null : $value;
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }
        $value = strtolower(trim($status));
        switch ($value) {
            case 'draft':
                return 'draft';
            case 'issued':
                return 'issued';
            case 'none':
            case 'sin':
            case 'sin_lqi':
            case 'without':
                return 'none';
            default:
                return null;
        }
    }

    private function fetchContracts(Carbon $period, $contractId): EloquentCollection
    {
        $query = Contract::query()
            ->with(['property', 'mainTenant.client', 'collectionBooklet.voucherType', 'settlementBooklet.voucherType']);

        if ($contractId) {
            $query->where('id', $contractId);
        } else {
            $query->activeDuring($period);
        }

        $contracts = $query->get();

        return $contracts;
    }

    private function fetchCharges(array $contractIds, Carbon $period, ?string $currency): Collection
    {
        if (empty($contractIds)) {
            return collect();
        }

        $start = $period->copy()->startOfMonth();
        $end   = $period->copy()->addMonth();
        Log::info('LqiOverviewService.fetchCharges', [
            'contractIds' => $contractIds,
            'period' => $period,
            'currency' => $currency,
            'start' => $start,
            'end' => $end,
        ]);

        return ContractCharge::query()
            ->select([
                'contract_charges.contract_id',
                DB::raw('UPPER(contract_charges.currency) as currency'),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN 1 ELSE 0 END) as add_count"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'subtract' THEN 1 ELSE 0 END) as subtract_count"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN contract_charges.amount ELSE 0 END) as add_total"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'subtract' THEN contract_charges.amount ELSE 0 END) as subtract_total"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN ABS(contract_charges.amount) ELSE 0 END) as add_total_abs"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'subtract' THEN ABS(contract_charges.amount) ELSE 0 END) as subtract_total_abs"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN contract_charges.amount WHEN charge_types.tenant_impact = 'subtract' THEN -contract_charges.amount ELSE 0 END) as net_total"),
            ])
            ->whereIn('contract_charges.contract_id', $contractIds)
            ->whereDate('contract_charges.effective_date', '>=', $start->toDateString())
            ->whereDate('contract_charges.effective_date', '<',  $end->toDateString())
            ->where('contract_charges.is_canceled', false)
            ->whereNull('contract_charges.tenant_liquidation_settled_at')
            ->join('charge_types', 'contract_charges.charge_type_id', '=', 'charge_types.id')
            ->whereIn('charge_types.tenant_impact', ['add', 'subtract'])
            ->when($currency && $currency !== 'ALL', function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(contract_charges.currency)'), $currency);
            })
            ->groupBy('contract_charges.contract_id', DB::raw('UPPER(contract_charges.currency)'))
            ->get();
    }

    private function fetchVouchers(array $contractIds, Carbon $period, ?string $currency, int $voucherTypeId): Collection
    {
        if (empty($contractIds)) {
            return collect();
        }

        return Voucher::query()
            ->select(['id', 'contract_id', 'currency', 'status', 'total', 'issue_date'])
            ->where('voucher_type_id', $voucherTypeId)
            ->whereDate('period', $period->copy()->startOfMonth()->toDateString())
            ->whereIn('contract_id', $contractIds)
            ->when($currency && $currency !== 'ALL', function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(currency)'), $currency);
            })
            ->withCount([
                'items as items_count',
                'applications as applications_count',
                'payments as payments_count',
            ])
            ->get();
    }

    private function fetchCreditNotes(array $contractIds, Carbon $period, ?string $currency): Collection
    {
        if (empty($contractIds)) {
            return collect();
        }

        $typeIds = $this->resolveCreditNoteTypeIds();
        if (empty($typeIds)) {
            return collect();
        }

        return Voucher::query()
            ->select(['id', 'contract_id', 'currency', 'status', 'total', 'issue_date'])
            ->whereIn('voucher_type_id', $typeIds)
            ->whereDate('period', $period->copy()->startOfMonth()->toDateString())
            ->whereIn('contract_id', $contractIds)
            ->when($currency && $currency !== 'ALL', function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(currency)'), $currency);
            })
            ->withCount(['items as items_count', 'voucherAssociations as associations_count'])
            ->get();
    }

    private function fetchPeriodCharges(array $contractIds, Carbon $period, ?string $currency): Collection
    {
        if (empty($contractIds)) {
            return collect();
        }

        $start = $period->copy()->startOfMonth();
        $end   = $period->copy()->addMonth();

        return ContractCharge::query()
            ->select([
                'contract_id',
                DB::raw('UPPER(currency) as currency'),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN contract_charges.amount ELSE 0 END) as add_total"),
                DB::raw("SUM(CASE WHEN charge_types.tenant_impact = 'add' THEN 1 ELSE 0 END) as add_count"),
            ])
            ->whereIn('contract_id', $contractIds)
            ->whereDate('effective_date', '>=', $start->toDateString())
            ->whereDate('effective_date', '<', $end->toDateString())
            ->where('is_canceled', false)
            ->join('charge_types', 'contract_charges.charge_type_id', '=', 'charge_types.id')
            ->where('charge_types.tenant_impact', 'add')
            ->when($currency && $currency !== 'ALL', function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(contract_charges.currency)'), $currency);
            })
            ->groupBy('contract_id', DB::raw('UPPER(currency)'))
            ->get();
    }

    private function buildChargesCoverageStats(array $contractIds, Carbon $period, ?string $currency, array $blockedContractIds): array
    {
        if (empty($contractIds)) {
            return [
                'per_currency' => [],
                'overall' => [
                    'universe' => 0,
                    'touched'  => 0,
                    'issued'   => 0,
                ],
            ];
        }

        $start = $period->copy()->startOfMonth();
        $end   = $period->copy()->addMonth();
        $voucherTypeIds = $this->resolveChargesVoucherTypeIds();

        $charges = ContractCharge::query()
            ->select([
                'contract_charges.id',
                'contract_charges.contract_id',
                DB::raw('UPPER(contract_charges.currency) as currency'),
                DB::raw("MAX(CASE WHEN vouchers.status IN ('draft', 'issued') THEN 1 ELSE 0 END) as touched"),
                DB::raw("MAX(CASE WHEN vouchers.status = 'issued' THEN 1 ELSE 0 END) as issued"),
            ])
            ->whereIn('contract_charges.contract_id', $contractIds)
            ->whereDate('contract_charges.effective_date', '>=', $start->toDateString())
            ->whereDate('contract_charges.effective_date', '<', $end->toDateString())
            ->where('contract_charges.is_canceled', false)
            ->join('charge_types', 'contract_charges.charge_type_id', '=', 'charge_types.id')
            ->whereIn('charge_types.tenant_impact', ['add', 'subtract'])
            ->when($currency && $currency !== 'ALL', function ($query) use ($currency) {
                $query->where(DB::raw('UPPER(contract_charges.currency)'), $currency);
            })
            ->when(!empty($blockedContractIds), function ($query) use ($blockedContractIds) {
                $query->whereNotIn('contract_charges.contract_id', $blockedContractIds);
            })
            ->leftJoin('voucher_items', function ($join) {
                $join->on('voucher_items.contract_charge_id', '=', 'contract_charges.id');
            })
            ->leftJoin('vouchers', function ($join) use ($voucherTypeIds) {
                $join->on('voucher_items.voucher_id', '=', 'vouchers.id')
                    ->whereIn('vouchers.voucher_type_id', $voucherTypeIds);
            })
            ->groupBy('contract_charges.id', 'contract_charges.contract_id', DB::raw('UPPER(contract_charges.currency)'))
            ->get();

        $perCurrency = [];
        $overall = [
            'universe' => 0,
            'touched'  => 0,
            'issued'   => 0,
        ];

        foreach ($charges as $charge) {
            $currency = $charge->currency ?? 'ARS';
            if (!isset($perCurrency[$currency])) {
                $perCurrency[$currency] = [
                    'universe' => 0,
                    'touched'  => 0,
                    'issued'   => 0,
                ];
            }

            $perCurrency[$currency]['universe']++;
            $overall['universe']++;

            if ((int) ($charge->touched ?? 0) > 0) {
                $perCurrency[$currency]['touched']++;
                $overall['touched']++;
            }

            if ((int) ($charge->issued ?? 0) > 0) {
                $perCurrency[$currency]['issued']++;
                $overall['issued']++;
            }
        }

        return [
            'per_currency' => $perCurrency,
            'overall' => $overall,
        ];
    }

    private function resolveVoucherTypeId(): int
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $id = VoucherType::query()->where('short_name', 'LQI')->value('id');
        if (!$id) {
            throw ValidationException::withMessages([
                'voucher_type' => 'No se encontró el tipo de comprobante LQI configurado.',
            ]);
        }

        $cache = (int) $id;
        return $cache;
    }

    private function resolveCreditNoteTypeIds(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $ids = VoucherType::query()
            ->where('short_name', 'N/C')
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        $cache = $ids;
        return $cache;
    }

    private function resolveChargesVoucherTypeIds(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $ids = VoucherType::query()
            ->whereIn('short_name', ['LQI', 'N/C', 'N/D'])
            ->pluck('id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->all();

        $cache = $ids;
        return $cache;
    }

    private function makeBaseRow(?Contract $contract, string $currency): array
    {
        $contractId = $contract ? $contract->id : null;

        $tenantRelation = null;
        if ($contract) {
            if ($contract->relationLoaded('mainTenant')) {
                $tenantRelation = $contract->mainTenant;
            } else {
                $tenantRelation = $contract->mainTenant()->with('client')->first();
            }
        }

        $tenant = $tenantRelation ? $tenantRelation->client : null;
        $tenantName = $tenant ? ($tenant->full_name ?? $tenant->name) : null;

        $property = $contract ? $contract->property : null;
        $propertyLabel = null;
        if ($property) {
            $parts = collect([
                $property->street,
                $property->number,
            ])->filter()->implode(' ');
            $propertyLabel = $parts ?: null;
        }

        return [
            'row_key'                => $this->rowKey($contractId, $currency),
            'contract_id'            => $contractId,
            'contract_property'      => $propertyLabel,
            'tenant_name'            => $tenantName,
            'contract_currency'      => ($contract && $contract->currency) ? strtoupper($contract->currency) : null,
            'currency'               => $currency,
            'eligible_count'         => 0,
            'eligible_total'         => 0.0,
            'eligible_total_abs'     => 0.0,
            'add_count'              => 0,
            'add_total'              => 0.0,
            'add_total_abs'          => 0.0,
            'add_pending_total'      => 0.0,
            'subtract_count'         => 0,
            'subtract_total'         => 0.0,
            'subtract_total_abs'     => 0.0,
            'subtract_pending_total' => 0.0,
            'net_total'              => 0.0,
            'has_positive'           => false,
            'has_negative'           => false,
            'nc_suggested'           => false,
            'only_credit'            => false,
            'only_credits'           => false,
            'has_eligibles'          => false,
            'status'                 => 'none',
            'voucher_id'             => null,
            'voucher_total'          => 0.0,
            'lqi_total'              => 0.0,
            'voucher_issue_date'     => null,
            'voucher_items_count'    => 0,
            'voucher_has_collections'=> false,
            'has_voucher'            => false,
            'can_sync'               => true,
            'can_issue'              => false,
            'can_reopen'             => false,
            'alerts'                 => [],
            'blocked'                => false,
            'blocked_reasons'        => [],
            'credit_note_id'         => null,
            'credit_note_status'     => null,
            'credit_note_total'      => 0.0,
            'credit_note_issue_date' => null,
            'credit_note_items_count'=> 0,
            'missing_rent'           => false,
            'pending_adjustment'     => false,
            'info_badges'            => [],
        ];
    }

    private function rowKey(?int $contractId, ?string $currency): string
    {
        $id = $contractId ?? 0;
        $currency = $currency ? strtoupper($currency) : 'ARS';
        return $id . '|' . $currency;
    }

    private function normalizeVoucherStatus(null|string|VoucherStatus $status): ?VoucherStatus
    {
        if ($status instanceof VoucherStatus) {
            return $status;
        }

        if (is_string($status)) {
            $normalized = $status === 'canceled' ? VoucherStatus::Cancelled->value : $status;
            return VoucherStatus::tryFrom($normalized);
        }

        return null;
    }

    private function contractCurrencyMismatch(Contract $contract, string $currency): bool
    {
        if (!$contract->currency) {
            return false;
        }
        return strtoupper($contract->currency) !== $currency;
    }

    private function resolveContractCurrencies(
        ?Contract $contract,
        ?string $currencyFilter,
        array $chargeCurrencies,
        array $voucherCurrencies
    ): array {
        $auto = !$currencyFilter || $currencyFilter === 'ALL';
        if (!$auto && $currencyFilter) {
            if (isset($chargeCurrencies[$currencyFilter]) || isset($voucherCurrencies[$currencyFilter])) {
                return [$currencyFilter];
            }

            return [];
        }

        $set = [];
        foreach (array_keys($chargeCurrencies) as $currency) {
            $set[$currency] = true;
        }
        foreach (array_keys($voucherCurrencies) as $currency) {
            $set[$currency] = true;
        }

        if (empty($set) && $contract) {
            $fallback = $contract->currency ? strtoupper($contract->currency) : 'ARS';
            $set[$fallback] = true;
        }

        return array_keys($set);
    }

    private function applyContractBlocks(array &$row, int $contractId, array $pendingAdjustments, array $rentStatus, bool $requireRentBeforeAny): void
    {
        $row['alerts'] = $row['alerts'] ?? [];
        $row['blocked_reasons'] = $row['blocked_reasons'] ?? [];
        $row['blocked'] = $row['blocked'] ?? false;
        $row['pending_adjustment'] = $row['pending_adjustment'] ?? false;
        $row['missing_rent'] = $row['missing_rent'] ?? false;
        $row['info_badges'] = $row['info_badges'] ?? [];

        if (isset($pendingAdjustments[$contractId])) {
            if (!in_array('pending_adjustment', $row['blocked_reasons'], true)) {
                $row['blocked_reasons'][] = 'pending_adjustment';
            }
            if (!in_array('pending_adjustment', $row['alerts'], true)) {
                $row['alerts'][] = 'pending_adjustment';
            }
            $row['blocked'] = true;
            $row['pending_adjustment'] = true;
        }

        $rentInfo = $rentStatus[$contractId] ?? null;
        $currency = $row['currency'] ?? null;

        $missing = $this->isRowMissingRent($rentInfo ?? [], $currency ?? '', $requireRentBeforeAny);

        if ($missing) {
            if (!in_array('missing_rent', $row['blocked_reasons'], true)) {
                $row['blocked_reasons'][] = 'missing_rent';
            }
            if (!in_array('missing_rent', $row['alerts'], true)) {
                $row['alerts'][] = 'missing_rent';
            }
            $row['blocked'] = true;
            $row['missing_rent'] = true;
            return;
        }

        $rentCurrency = $rentInfo['rent_currency'] ?? ($row['contract_currency'] ?? null);
        if (!$requireRentBeforeAny && $rentCurrency && $currency !== $rentCurrency) {
            $existing = array_filter($row['info_badges'], function ($badge) {
                return ($badge['key'] ?? null) === 'missing_rent_other_currency';
            });

            if (empty($existing)) {
                $row['info_badges'][] = [
                    'key' => 'missing_rent_other_currency',
                    'message' => "Primera LQI en {$currency} (sin RENT en {$currency})",
                    'rent_currency' => $rentCurrency,
                ];
            }
        }
    }

    private function isContractMissingRent(array $rentInfo, bool $requireRentBeforeAny): bool
    {
        if (empty($rentInfo)) {
            return $requireRentBeforeAny;
        }

        if ($requireRentBeforeAny) {
            return !($rentInfo['has_any_rent'] ?? false);
        }

        $rentCurrency = $rentInfo['rent_currency'] ?? null;
        if ($rentCurrency) {
            return ($rentInfo['missing_per_currency'][$rentCurrency] ?? false) === true;
        }

        return !($rentInfo['has_any_rent'] ?? false);
    }

    private function isRowMissingRent(array $rentInfo, string $currency, bool $requireRentBeforeAny): bool
    {
        if (empty($rentInfo)) {
            return $requireRentBeforeAny;
        }

        if ($requireRentBeforeAny) {
            return $this->isContractMissingRent($rentInfo, true);
        }

        $rentCurrency = $rentInfo['rent_currency'] ?? null;
        if ($rentCurrency) {
            if ($currency === $rentCurrency) {
                return ($rentInfo['missing_per_currency'][$rentCurrency] ?? false) === true;
            }

            return false;
        }

        return !($rentInfo['has_any_rent'] ?? false);
    }

    private function detectPendingAdjustments(array $contractIds, Carbon $period): array
    {
        if (empty($contractIds)) {
            return [];
        }

        return ContractAdjustment::query()
            ->select('contract_id')
            ->whereIn('contract_id', $contractIds)
            ->blockingForPeriod($period)
            ->pluck('contract_id')
            ->unique()
            ->mapWithKeys(function ($id) {
                return [(int) $id => true];
            })
            ->all();
    }

    private function detectMissingRent(array $contractIds, Carbon $period, EloquentCollection $contracts): array
    {
        if (empty($contractIds)) {
            return [];
        }

        $start = $period->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        $rentCharges = ContractCharge::query()
            ->select([
                'contract_id',
                DB::raw('UPPER(currency) as currency'),
            ])
            ->whereIn('contract_id', $contractIds)
            ->whereDate('effective_date', '>=', $start->toDateString())
            ->whereDate('effective_date', '<', $end->toDateString())
            ->where('is_canceled', false)
            ->whereHas('chargeType', function ($query) {
                $query->where('code', ChargeType::CODE_RENT);
            })
            ->groupBy('contract_id', DB::raw('UPPER(currency)'))
            ->get()
            ->groupBy('contract_id')
            ->map(function ($rows) {
                return $rows->pluck('currency')->unique()->values()->all();
            })
            ->all();

        $status = [];
        $contractsById = $contracts->keyBy('id');

        foreach ($contractIds as $id) {
            $contractId = (int) $id;
            $contract = $contractsById->get($contractId);
            $rentCurrency = $contract && $contract->currency ? strtoupper($contract->currency) : null;
            $currencies = $rentCharges[$contractId] ?? [];
            $hasAny = !empty($currencies);

            $missingPerCurrency = [];
            if ($rentCurrency) {
                $missingPerCurrency[$rentCurrency] = !in_array($rentCurrency, $currencies, true);
            }

            $status[$contractId] = [
                'rent_currency' => $rentCurrency,
                'currencies' => $currencies,
                'has_any_rent' => $hasAny,
                'missing_per_currency' => $missingPerCurrency,
            ];
        }

        return $status;
    }

    public function issueCreditNote(Contract $contract, Carbon $period, string $currency, ?Voucher $associatedVoucher = null): bool
    {
        $currency = strtoupper($currency);

        return DB::transaction(function () use ($contract, $period, $currency, $associatedVoucher) {
            $charges = $this->collectChargesForImpact($contract->id, $period, $currency, 'subtract');

            if ($charges->isEmpty()) {
                return false;
            }

            $total = $charges->sum(function (ContractCharge $charge) {
                return (float) $charge->amount;
            });
            if ($total <= 0) {
                return false;
            }

            $bookletId = $this->resolveCreditNoteBookletId($contract);
            $tenantSnapshot = $this->resolveTenantSnapshot($contract);

            if (!($tenantSnapshot['client_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'client_id' => 'El contrato no tiene inquilino principal asignado.',
                ]);
            }

            $items = $charges->map(function (ContractCharge $charge) {
                return [
                    'type' => 'charge',
                    'description' => $this->buildChargeDescription($charge),
                    'quantity' => 1,
                    'unit_price' => $charge->amount,
                    'tax_rate_id' => null,
                    'contract_charge_id' => $charge->id,
                    'impact' => 'subtract',
                ];
            })->values()->all();
            $payload = array_merge([
                'booklet_id' => $bookletId,
                'voucher_type_short_name'   => 'N/C',
                'contract_id' => $contract->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'period' => $period->copy()->startOfMonth()->toDateString(),
                'currency' => $currency,
                'generated_from_collection' => false,
                'items' => $items,
                'meta' => [
                    'lqi_credit_note' => true,
                ],
            ], $tenantSnapshot);

            if ($associatedVoucher) {
                $payload['associated_voucher_ids'] = [$associatedVoucher->id];
            }

            $creditNote = $this->voucherService->createFromArray($payload);
            $this->voucherService->issue($creditNote);

            $this->markChargesSettled($charges, $creditNote->id);

            return true;
        });
    }

    private function collectChargesForImpact(int $contractId, Carbon $period, string $currency, string $impact): Collection
    {
        $from = $period->copy()->startOfMonth();
        $to = $from->copy()->addMonth();

        return ContractCharge::query()
            ->with('chargeType')
            ->where('contract_id', $contractId)
            ->whereDate('effective_date', '>=', $from->toDateString())
            ->whereDate('effective_date', '<', $to->toDateString())
            ->where('currency', strtoupper($currency))
            ->where('is_canceled', false)
            ->whereNull('tenant_liquidation_settled_at')
            ->whereHas('chargeType', function ($query) use ($impact) {
                $query->where('tenant_impact', $impact);
            })
            ->lockForUpdate()
            ->get();
    }

    private function resolveCreditNoteBookletId(Contract $contract): int
    {
        $candidates = [
            $contract->collectionBooklet,
            $contract->settlementBooklet,
        ];

        foreach ($candidates as $booklet) {
            if (!$booklet) {
                continue;
            }

            $voucherType = $booklet->voucherType;
            if ($voucherType && $voucherType->short_name === 'N/C') {
                return $booklet->id;
            }
        }

        $booklet = Booklet::query()
            ->whereHas('voucherType', function ($query) {
                $query->where('short_name', 'N/C');
            })
            ->orderBy('id')
            ->first();

        if (!$booklet) {
            throw ValidationException::withMessages([
                'credit_note' => 'No se encontró talonario configurado para Notas de Crédito.',
            ]);
        }

        return $booklet->id;
    }

    private function resolveTenantSnapshot(Contract $contract): array
    {
        $tenantContractClient = $contract->mainTenant()->with('client')->first();

        $clientId = $tenantContractClient ? $tenantContractClient->client_id : null;
        $client   = $tenantContractClient ? $tenantContractClient->client : null;

        return [
            'client_id' => $clientId,
            'client_name' => $client ? $client->name : null,
            'client_address' => $client ? $client->address : null,
            'client_document_type_name' => $client ? $client->document_type_name : null,
            'client_document_number' => $client ? $client->document_number : null,
            'client_tax_condition_name' => $client ? $client->tax_condition_name : null,
            'client_tax_id_number' => $client ? $client->tax_id_number : null,
        ];
    }

    private function buildChargeDescription(ContractCharge $charge): string
    {
        $type = $charge->chargeType ? $charge->chargeType->name : 'Cargo';
        $ym   = Carbon::parse($charge->effective_date)->format('Y-m');
        return "{$type} {$ym}" . ($charge->description ? " – {$charge->description}" : '');
    }

    private function markChargesSettled(Collection $charges, int $voucherId): void
    {
        $now = now();
        foreach ($charges as $charge) {
            /** @var ContractCharge $charge */
            $charge->tenant_liquidation_voucher_id = $voucherId;
            $charge->tenant_liquidation_settled_at = $now;
            $charge->save();
        }
    }

    private function buildKpis(
        array $rows,
        EloquentCollection $contracts,
        array $alertsSummary,
        array $eligibilityPairs,
        array $issuanceUniverse,
        array $issuanceIssued,
        array $creditSummary,
        array $chargesCoverageStats
    ): array
    {
        $currencyMetrics = [];
        $overallDraftCount = 0;
        $overallDraftTotal = 0.0;
        $overallIssuedCount = 0;
        $overallIssuedTotal = 0.0;
        $overallCreditContracts = [];
        $overallCreditOnlyContracts = [];

        foreach ($rows as $row) {
            $currency = $row['currency'] ?? 'ARS';
            $rowKey = $row['row_key'] ?? $this->rowKey($row['contract_id'] ?? null, $currency);

            if (!isset($currencyMetrics[$currency])) {
                $currencyMetrics[$currency] = [
                    'currency' => $currency,
                    'draft_count' => 0,
                    'draft_total' => 0.0,
                    'issued_count' => 0,
                    'issued_total' => 0.0,
                    'credit_contracts' => [],
                    'credit_only_contracts' => [],
                ];
            }

            $status = $row['status'] ?? 'none';
            if ($status === 'draft') {
                $currencyMetrics[$currency]['draft_count']++;
                $currencyMetrics[$currency]['draft_total'] += (float) ($row['voucher_total'] ?? 0.0);
                $overallDraftCount++;
                $overallDraftTotal += (float) ($row['voucher_total'] ?? 0.0);
            }

            if ($status === 'issued') {
                $currencyMetrics[$currency]['issued_count']++;
                $currencyMetrics[$currency]['issued_total'] += (float) ($row['voucher_total'] ?? 0.0);
                $overallIssuedCount++;
                $overallIssuedTotal += (float) ($row['voucher_total'] ?? 0.0);
            }

            if (!empty($row['has_negative'])) {
                $currencyMetrics[$currency]['credit_contracts'][$rowKey] = true;
                $overallCreditContracts[$rowKey] = true;
            }

            if (!empty($row['only_credit']) || !empty($row['only_credits'])) {
                $currencyMetrics[$currency]['credit_only_contracts'][$rowKey] = true;
                $overallCreditOnlyContracts[$rowKey] = true;
            }
        }

        $eligibilityByCurrency = [];
        $overallEligibility = [
            'eligible_pairs' => 0,
            'processable_pairs' => 0,
            'eligible_total' => 0.0,
            'blocked_pending' => 0,
            'blocked_missing' => 0,
            'blocked_pairs' => 0,
        ];

        foreach ($eligibilityPairs as $pair) {
            $currency = $pair['currency'] ?? 'ARS';
            $addTotal = (float) ($pair['add_total'] ?? 0.0);
            $blockedPending = !empty($pair['blocked_pending']);
            $blockedMissing = !empty($pair['blocked_missing']);
            $isBlocked = $blockedPending || $blockedMissing;

            if (!isset($eligibilityByCurrency[$currency])) {
                $eligibilityByCurrency[$currency] = [
                    'eligible_pairs' => 0,
                    'processable_pairs' => 0,
                    'eligible_total' => 0.0,
                    'blocked_pending' => 0,
                    'blocked_missing' => 0,
                    'blocked_pairs' => 0,
                ];
            }

            $eligibilityByCurrency[$currency]['eligible_pairs']++;
            $eligibilityByCurrency[$currency]['eligible_total'] += $addTotal;
            if ($blockedPending) {
                $eligibilityByCurrency[$currency]['blocked_pending']++;
            }
            if ($blockedMissing) {
                $eligibilityByCurrency[$currency]['blocked_missing']++;
            }
            if ($isBlocked) {
                $eligibilityByCurrency[$currency]['blocked_pairs']++;
            }
            if (!$isBlocked) {
                $eligibilityByCurrency[$currency]['processable_pairs']++;
            }

            $overallEligibility['eligible_pairs']++;
            $overallEligibility['eligible_total'] += $addTotal;
            if ($blockedPending) {
                $overallEligibility['blocked_pending']++;
            }
            if ($blockedMissing) {
                $overallEligibility['blocked_missing']++;
            }
            if (!$isBlocked) {
                $overallEligibility['processable_pairs']++;
            }
            if ($isBlocked) {
                $overallEligibility['blocked_pairs']++;
            }
        }

        $issuanceUniversePerCurrency = $issuanceUniverse['per_currency'] ?? [];
        $issuanceIssuedPerCurrency = $issuanceIssued['per_currency'] ?? [];
        $chargesCoveragePerCurrency = $chargesCoverageStats['per_currency'] ?? [];

        $currencyKeys = array_unique(array_merge(
            array_keys($currencyMetrics),
            array_keys($eligibilityByCurrency),
            array_keys($issuanceUniversePerCurrency),
            array_keys($issuanceIssuedPerCurrency),
            array_keys($chargesCoveragePerCurrency)
        ));
        sort($currencyKeys);

        $issuanceCoverageInconsistency = false;
        $currencySummaries = [];

        foreach ($currencyKeys as $currency) {
            $metric = $currencyMetrics[$currency] ?? [
                'draft_count' => 0,
                'draft_total' => 0.0,
                'issued_count' => 0,
                'issued_total' => 0.0,
                'credit_contracts' => [],
                'credit_only_contracts' => [],
            ];

            $eligibility = $eligibilityByCurrency[$currency] ?? [
                'eligible_pairs' => 0,
                'processable_pairs' => 0,
                'eligible_total' => 0.0,
                'blocked_pending' => 0,
                'blocked_missing' => 0,
            ];

            $contractsWithEligibles = $eligibility['eligible_pairs'];
            $contractsWithEligiblesProcessable = $eligibility['processable_pairs'];
            $eligibleTotal = (float) $eligibility['eligible_total'];
            $blockedPending = (int) $eligibility['blocked_pending'];
            $blockedMissing = (int) $eligibility['blocked_missing'];
            $blockedTotal = (int) ($eligibility['blocked_pairs'] ?? 0);

            $draftCount = (int) ($metric['draft_count'] ?? 0);
            $draftTotal = (float) ($metric['draft_total'] ?? 0.0);
            $issuedCount = (int) ($metric['issued_count'] ?? 0);
            $issuedTotal = (float) ($metric['issued_total'] ?? 0.0);

            $issuanceUniverseCurrency = $issuanceUniversePerCurrency[$currency] ?? ['count' => 0, 'total' => 0.0];
            $issuanceUniverseCount = (int) ($issuanceUniverseCurrency['count'] ?? 0);
            $issuanceUniverseTotal = (float) ($issuanceUniverseCurrency['total'] ?? 0.0);
            $issuanceIssuedCount = (int) ($issuanceIssuedPerCurrency[$currency]['count'] ?? 0);

            $issuanceCoverage = null;
            if ($issuanceUniverseCount > 0) {
                $issuanceCoverage = round(($issuanceIssuedCount / $issuanceUniverseCount) * 100, 1);
            } elseif ($issuanceIssuedCount > 0) {
                $issuanceCoverageInconsistency = true;
            }

            $chargesCoverageData = $chargesCoveragePerCurrency[$currency] ?? ['universe' => 0, 'touched' => 0, 'issued' => 0];
            $chargesUniverse = (int) ($chargesCoverageData['universe'] ?? 0);
            $chargesTouched = (int) ($chargesCoverageData['touched'] ?? 0);
            $chargesIssued = (int) ($chargesCoverageData['issued'] ?? 0);

            $chargesCoverage = null;
            $chargesCoverageIssued = null;
            if ($chargesUniverse > 0) {
                $chargesCoverage = round(($chargesTouched / $chargesUniverse) * 100, 1);
                $chargesCoverageIssued = round(($chargesIssued / $chargesUniverse) * 100, 1);
            }

            $creditContractsCount = count($metric['credit_contracts'] ?? []);
            $creditOnlyCount = count($metric['credit_only_contracts'] ?? []);

            $currencySummaries[] = [
                'currency'                 => $currency,
                'contracts_with_eligibles' => $contractsWithEligibles,
                'contracts_with_eligibles_processable' => $contractsWithEligiblesProcessable,
                'eligible_total'           => $eligibleTotal,
                'blocked'                  => [
                    'pending_adjustment' => $blockedPending,
                    'missing_rent'       => $blockedMissing,
                    'total'              => $blockedTotal,
                ],
                'credit'                   => [
                    'total' => $creditContractsCount,
                    'only'  => $creditOnlyCount,
                ],
                'nc'                       => $creditSummary['per_currency'][$currency] ?? [
                    'issued_count' => 0,
                    'issued_total' => 0.0,
                    'associated_count' => 0,
                    'associated_total' => 0.0,
                    'standalone_count' => 0,
                    'standalone_total' => 0.0,
                ],
                'draft'                    => [
                    'count' => $draftCount,
                    'total' => $draftTotal,
                ],
                'issued'                   => [
                    'count' => $issuedCount,
                    'total' => $issuedTotal,
                ],
                'issuance_coverage'        => $issuanceCoverage,
                'issuance_coverage_detail' => [
                    'universe' => $issuanceUniverseCount,
                    'universe_total' => $issuanceUniverseTotal,
                    'issued'   => $issuanceIssuedCount,
                ],
                'charges_coverage'        => $chargesCoverage,
                'charges_coverage_issued' => $chargesCoverageIssued,
                'charges_coverage_detail' => [
                    'universe' => $chargesUniverse,
                    'touched'  => $chargesTouched,
                    'issued'   => $chargesIssued,
                ],
            ];
        }

        usort($currencySummaries, function ($a, $b) {
            return strcmp($a['currency'], $b['currency']);
        });

        $overallUniverse = $issuanceUniverse['overall']['count'] ?? 0;
        $overallUniverseTotal = $issuanceUniverse['overall']['total'] ?? 0.0;
        $overallIssuedCoverageCount = $issuanceIssued['overall']['count'] ?? 0;

        $overallIssuanceCoverage = null;
        if ($overallUniverse > 0) {
            $overallIssuanceCoverage = round(($overallIssuedCoverageCount / $overallUniverse) * 100, 1);
        } elseif ($overallIssuedCoverageCount > 0) {
            $issuanceCoverageInconsistency = true;
        }

        if ($issuanceCoverageInconsistency) {
            Log::warning('LqiOverviewService.issuance_coverage.inconsistency', [
                'universe' => $overallUniverse,
                'issued' => $overallIssuedCoverageCount,
            ]);
        }

        $chargesCoverageOverall = $chargesCoverageStats['overall'] ?? ['universe' => 0, 'touched' => 0, 'issued' => 0];
        $overallChargesUniverse = (int) ($chargesCoverageOverall['universe'] ?? 0);
        $overallChargesTouched = (int) ($chargesCoverageOverall['touched'] ?? 0);
        $overallChargesIssued = (int) ($chargesCoverageOverall['issued'] ?? 0);

        $overallChargesCoverage = null;
        $overallChargesCoverageIssued = null;
        if ($overallChargesUniverse > 0) {
            $overallChargesCoverage = round(($overallChargesTouched / $overallChargesUniverse) * 100, 1);
            $overallChargesCoverageIssued = round(($overallChargesIssued / $overallChargesUniverse) * 100, 1);
        }

        $overallBlockedPending = $overallEligibility['blocked_pending'];
        $overallBlockedMissing = $overallEligibility['blocked_missing'];
        $overallBlockedTotal = $overallEligibility['blocked_pairs'];

        $overall = [
            'active_contracts'         => $contracts->count(),
            'contracts_with_eligibles' => $overallEligibility['eligible_pairs'],
            'contracts_with_eligibles_processable' => $overallEligibility['processable_pairs'],
            'eligible_total'           => $overallEligibility['eligible_total'],
            'blocked'                  => [
                'pending_adjustment' => $overallBlockedPending,
                'missing_rent'       => $overallBlockedMissing,
                'total'              => $overallBlockedTotal,
            ],
            'credit'                   => [
                'total' => count($overallCreditContracts),
                'only'  => count($overallCreditOnlyContracts),
            ],
            'nc'                       => $creditSummary['overall'],
            'draft'                    => [
                'count' => $overallDraftCount,
                'total' => $overallDraftTotal,
            ],
            'issued'                   => [
                'count' => $overallIssuedCount,
                'total' => $overallIssuedTotal,
            ],
            'issuance_coverage'                 => $overallIssuanceCoverage,
            'issuance_coverage_detail'          => [
                'universe' => $overallUniverse,
                'universe_total' => $overallUniverseTotal,
                'issued'   => $overallIssuedCoverageCount,
            ],
            'charges_coverage'         => $overallChargesCoverage,
            'charges_coverage_issued'  => $overallChargesCoverageIssued,
            'charges_coverage_detail'  => [
                'universe' => $overallChargesUniverse,
                'touched'  => $overallChargesTouched,
                'issued'   => $overallChargesIssued,
            ],
        ];

        $alerts = [
            'missing_tenant'    => count($alertsSummary['missing_tenant'] ?? []),
            'currency_mismatch' => count($alertsSummary['currency_mismatch'] ?? []),
        ];

        return [
            'currencies' => $currencySummaries,
            'overall'    => $overall,
            'alerts'     => $alerts,
        ];
    }


    private function emptyKpis(): array
    {
        return [
            'currencies' => [],
            'overall' => [
                'active_contracts'         => 0,
                'contracts_with_eligibles' => 0,
                'contracts_with_eligibles_processable' => 0,
                'eligible_total'           => 0,
                'blocked'                  => [
                    'pending_adjustment' => 0,
                    'missing_rent'       => 0,
                    'total'              => 0,
                ],
                'credit'                  => [
                    'total' => 0,
                    'only'  => 0,
                ],
                'nc'                      => [
                    'issued_count' => 0,
                    'issued_total' => 0.0,
                    'associated_count' => 0,
                    'associated_total' => 0.0,
                    'standalone_count' => 0,
                    'standalone_total' => 0.0,
                ],
                'draft'                    => [
                    'count' => 0,
                    'total' => 0,
                ],
                'issued'                   => [
                    'count' => 0,
                    'total' => 0,
                ],
                'issuance_coverage'        => null,
                'issuance_coverage_detail' => [
                    'universe' => 0,
                    'universe_total' => 0.0,
                    'issued' => 0,
                ],
                'charges_coverage'         => null,
                'charges_coverage_issued'  => null,
                'charges_coverage_detail'  => [
                    'universe' => 0,
                    'touched'  => 0,
                    'issued'   => 0,
                ],
            ],
            'alerts' => [
                'missing_tenant'    => 0,
                'currency_mismatch' => 0,
            ],
        ];
    }
}
