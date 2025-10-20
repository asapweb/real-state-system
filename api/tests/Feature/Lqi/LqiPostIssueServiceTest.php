<?php

namespace Tests\Feature\Lqi;

use App\Enums\ChargeImpact;
use App\Enums\VoucherStatus;
use App\Enums\ContractAdjustmentType;
use App\Enums\ContractClientRole;
use App\Models\Booklet;
use App\Models\ChargeType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\ContractClient;
use App\Models\SalePoint;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\VoucherType;
use App\Services\LqiPostIssueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LqiPostIssueServiceTest extends TestCase
{
    use DatabaseTransactions;

    private VoucherType $lqiType;
    private VoucherType $creditType;
    private VoucherType $debitType;
    private Booklet $lqiBooklet;
    private Booklet $creditBooklet;
    private Booklet $debitBooklet;
    private ChargeType $rentChargeType;
    private ChargeType $addChargeType;
    private ChargeType $subtractChargeType;

    protected function setUp(): void
    {
        parent::setUp();

        config(['features.lqi.post_issue' => true]);

        $this->seedVoucherInfrastructure();
        $this->seedChargeTypes();
    }

    public function test_issues_debit_note_for_pending_adds_and_associates_with_lqi(): void
    {
        [$contract, $client, $periodDate, $lqiVoucher] = $this->createContractSetup();

        $this->seedRentCharge($contract, $periodDate, 'ARS', $lqiVoucher->id);

        $charge = $this->createCharge($contract, $this->addChargeType, 1500, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('ok', $result['result']);
        $this->assertTrue($result['nd']['issued']);
        $this->assertSame(1, $result['nd']['count']);
        $this->assertSame(1500.0, $result['nd']['total']);
        $this->assertFalse($result['nc']['issued']);
        $this->assertContains('issued_nd_associated_to_lqi', $result['notes']);

        $charge->refresh();
        $this->assertNotNull($charge->tenant_liquidation_settled_at);
        $this->assertSame($result['nd']['voucher_id'], $charge->tenant_liquidation_voucher_id);

        $debitVoucher = Voucher::findOrFail($result['nd']['voucher_id']);
        $this->assertSame('N/D', $debitVoucher->voucher_type_short_name);
        $this->assertSame(VoucherStatus::Issued, $debitVoucher->status);
        $this->assertEquals([$charge->id], $debitVoucher->items()->pluck('contract_charge_id')->all());
        $this->assertEquals([$lqiVoucher->id], $debitVoucher->associatedVouchers()->pluck('vouchers.id')->all());

        $second = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');
        $this->assertSame('nothing_to_issue', $second['result']);
        $this->assertFalse($second['nd']['issued']);
        $this->assertFalse($second['nc']['issued']);
    }

    public function test_issues_credit_note_for_pending_subtracts(): void
    {
        [$contract, $client, $periodDate, $lqiVoucher] = $this->createContractSetup();

        $this->seedRentCharge($contract, $periodDate, 'ARS', $lqiVoucher->id);

        $charge = $this->createCharge($contract, $this->subtractChargeType, 2400, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('ok', $result['result']);
        $this->assertTrue($result['nc']['issued']);
        $this->assertSame(1, $result['nc']['count']);
        $this->assertSame(2400.0, $result['nc']['total']);
        $this->assertFalse($result['nd']['issued']);
        $this->assertContains('issued_nc_associated_to_lqi', $result['notes']);

        $charge->refresh();
        $this->assertNotNull($charge->tenant_liquidation_settled_at);
        $this->assertSame($result['nc']['voucher_id'], $charge->tenant_liquidation_voucher_id);

        $creditVoucher = Voucher::findOrFail($result['nc']['voucher_id']);
        $this->assertSame('N/C', $creditVoucher->voucher_type_short_name);
        $this->assertEquals([$charge->id], $creditVoucher->items()->pluck('contract_charge_id')->all());
        $this->assertEquals([$lqiVoucher->id], $creditVoucher->associatedVouchers()->pluck('vouchers.id')->all());
    }

    public function test_issues_both_notes_when_add_and_subtract_present(): void
    {
        [$contract, $client, $periodDate, $lqiVoucher] = $this->createContractSetup();

        $this->seedRentCharge($contract, $periodDate, 'ARS', $lqiVoucher->id);

        $addCharge = $this->createCharge($contract, $this->addChargeType, 1500, $periodDate, 'ARS');
        $subtractCharge = $this->createCharge($contract, $this->subtractChargeType, 800, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('ok', $result['result']);
        $this->assertTrue($result['nd']['issued']);
        $this->assertTrue($result['nc']['issued']);
        $this->assertSame(1500.0, $result['nd']['total']);
        $this->assertSame(800.0, $result['nc']['total']);

        $this->assertEquals([$addCharge->id], VoucherItem::where('voucher_id', $result['nd']['voucher_id'])->pluck('contract_charge_id')->all());
        $this->assertEquals([$subtractCharge->id], VoucherItem::where('voucher_id', $result['nc']['voucher_id'])->pluck('contract_charge_id')->all());
    }

    public function test_issues_credit_note_without_lqi_when_only_negative_charges(): void
    {
        [$contract, $client, $periodDate] = $this->createContractSetup(withLqi: false);

        $this->seedRentCharge($contract, $periodDate, 'ARS', voucherId: null, settled: true);

        $charge = $this->createCharge($contract, $this->subtractChargeType, 500, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('ok', $result['result']);
        $this->assertTrue($result['nc']['issued']);
        $this->assertFalse($result['nd']['issued']);
        $this->assertContains('issued_nc_standalone', $result['notes']);

        $charge->refresh();
        $this->assertSame($result['nc']['voucher_id'], $charge->tenant_liquidation_voucher_id);
    }

    public function test_returns_invalid_status_when_lqi_missing_for_adds(): void
    {
        [$contract, $client, $periodDate] = $this->createContractSetup(withLqi: false);

        $this->seedRentCharge($contract, $periodDate, 'ARS', voucherId: null, settled: true);

        $this->createCharge($contract, $this->addChargeType, 1000, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('invalid_status_for_post_issue', $result['result']);
        $this->assertSame('none', $result['status']);
        $this->assertContains('lqi_required', $result['reasons'] ?? []);
        $this->assertSame([
            ['action' => 'issue_lqi'],
        ], $result['suggestions'] ?? []);
    }

    public function test_returns_invalid_status_for_standalone_nc_suggested(): void
    {
        [$contract, $client, $periodDate] = $this->createContractSetup(withLqi: false);

        $this->seedRentCharge($contract, $periodDate, 'ARS', voucherId: null, settled: true);

        $this->createCharge($contract, $this->subtractChargeType, 500, $periodDate, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('invalid_status_for_post_issue', $result['result']);
        $this->assertSame('none', $result['status']);
        $this->assertContains('standalone_nc_available', $result['reasons'] ?? []);
        $this->assertSame([
            ['action' => 'issue_nc_alone'],
        ], $result['suggestions'] ?? []);
    }

    public function test_returns_invalid_status_when_lqi_is_draft(): void
    {
        [$contract, $client, $periodDate] = $this->createContractSetup(withLqi: false);

        $this->seedRentCharge($contract, $periodDate, 'ARS', voucherId: null, settled: true);
        $this->createCharge($contract, $this->addChargeType, 1200, $periodDate, 'ARS');

        $draftVoucher = Voucher::create([
            'booklet_id' => $this->lqiBooklet->id,
            'voucher_type_id' => $this->lqiType->id,
            'voucher_type_short_name' => 'LQI',
            'voucher_type_letter' => $this->lqiType->letter,
            'sale_point_number' => $this->lqiBooklet->salePoint->number,
            'number' => 2,
            'issue_date' => $periodDate->copy()->endOfMonth()->toDateString(),
            'due_date' => $periodDate->copy()->endOfMonth()->toDateString(),
            'period' => $periodDate->toDateString(),
            'generated_from_collection' => false,
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_address' => $client->address,
            'client_document_type_name' => $client->document_type_name,
            'client_document_number' => $client->document_number,
            'client_tax_condition_name' => $client->tax_condition_name,
            'client_tax_id_number' => $client->tax_id_number,
            'contract_id' => $contract->id,
            'status' => VoucherStatus::Draft->value,
            'currency' => 'ARS',
            'subtotal_taxed' => 0,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('invalid_status_for_post_issue', $result['result']);
        $this->assertSame('draft', $result['status']);
        $this->assertContains('draft_requires_issue', $result['reasons'] ?? []);
        $this->assertSame([
            ['action' => 'issue_lqi'],
        ], $result['suggestions'] ?? []);

        $draftVoucher->delete();
    }

    public function test_returns_blocked_when_pending_adjustment_detected(): void
    {
        [$contract, $client, $periodDate, $lqiVoucher] = $this->createContractSetup();

        $this->seedRentCharge($contract, $periodDate, 'ARS', $lqiVoucher->id);

        ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'effective_date' => $periodDate->copy()->startOfMonth()->toDateString(),
            'type' => ContractAdjustmentType::INDEX,
            'applied_at' => null,
            'applied_amount' => null,
        ]);

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('blocked', $result['result']);
        $this->assertContains('pending_adjustment', $result['reasons']);
    }

    public function test_returns_nothing_to_issue_when_no_pending_charges(): void
    {
        [$contract, $client, $periodDate, $lqiVoucher] = $this->createContractSetup();

        $this->seedRentCharge($contract, $periodDate, 'ARS', $lqiVoucher->id);

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustments($contract->id, $periodDate->format('Y-m'), 'ARS');

        $this->assertSame('nothing_to_issue', $result['result']);
        $this->assertFalse($result['nd']['issued']);
        $this->assertFalse($result['nc']['issued']);
    }

    public function test_bulk_issues_adjustments_and_collects_metrics(): void
    {
        [$contractA, $clientA, $periodDate, $lqiVoucherA] = $this->createContractSetup();
        $this->seedRentCharge($contractA, $periodDate, 'ARS', $lqiVoucherA->id);
        $addChargeA = $this->createCharge($contractA, $this->addChargeType, 1500, $periodDate, 'ARS');
        $subtractChargeA = $this->createCharge($contractA, $this->subtractChargeType, 800, $periodDate, 'ARS');

        [$contractB, $clientB, $periodDateB] = $this->createContractSetup(withLqi: false);
        $this->seedRentCharge($contractB, $periodDateB, 'ARS', voucherId: null, settled: true);
        $subtractChargeB = $this->createCharge($contractB, $this->subtractChargeType, 500, $periodDateB, 'ARS');

        [$contractC, $clientC, $periodDateC, $lqiVoucherC] = $this->createContractSetup();
        $this->seedRentCharge($contractC, $periodDateC, 'ARS', $lqiVoucherC->id);
        ContractAdjustment::factory()->create([
            'contract_id' => $contractC->id,
            'effective_date' => $periodDateC->copy()->startOfMonth()->toDateString(),
            'type' => ContractAdjustmentType::INDEX,
            'applied_at' => null,
            'applied_amount' => null,
        ]);

        [$contractD, $clientD, $periodDateD] = $this->createContractSetup(withLqi: false);
        $this->seedRentCharge($contractD, $periodDateD, 'ARS', voucherId: null, settled: true);
        $this->createCharge($contractD, $this->addChargeType, 900, $periodDateD, 'ARS');

        $service = app(LqiPostIssueService::class);
        $result = $service->issueAdjustmentsBulk('2025-01', ['currency' => 'ARS']);

        $this->assertSame(4, $result['processed']);
        $this->assertSame(1, $result['nd_issued']['count']);
        $this->assertSame(1500.0, $result['nd_issued']['total']);
        $this->assertSame(2, $result['nc_issued']['count']);
        $this->assertSame(1300.0, $result['nc_issued']['total']);

        $reasons = collect($result['skipped'])->pluck('reason')->all();
        $this->assertContains('blocked', $reasons);
        $this->assertContains('invalid_status_for_post_issue', $reasons);

        $this->assertNotNull($addChargeA->refresh()->tenant_liquidation_settled_at);
        $this->assertNotNull($subtractChargeA->refresh()->tenant_liquidation_settled_at);
        $this->assertNotNull($subtractChargeB->refresh()->tenant_liquidation_settled_at);

        $skipped = collect($result['skipped']);
        $lqiRequiredEntry = $skipped->first(function ($item) {
            return ($item['reason'] ?? null) === 'invalid_status_for_post_issue'
                && in_array('lqi_required', $item['reasons'] ?? [], true);
        });
        $this->assertNotNull($lqiRequiredEntry);
        $this->assertSame('none', $lqiRequiredEntry['status']);
        $this->assertSame(900.0, $lqiRequiredEntry['add_total']);
        $this->assertSame([
            ['action' => 'issue_lqi'],
        ], $lqiRequiredEntry['suggestions'] ?? []);

        $standaloneEntry = $skipped->first(function ($item) {
            return ($item['reason'] ?? null) === 'invalid_status_for_post_issue'
                && in_array('standalone_nc_available', $item['reasons'] ?? [], true);
        });
        $this->assertNotNull($standaloneEntry);
        $this->assertSame('none', $standaloneEntry['status']);
        $this->assertSame([
            ['action' => 'issue_nc_alone'],
        ], $standaloneEntry['suggestions'] ?? []);

        $draftEntry = $skipped->first(function ($item) {
            return ($item['reason'] ?? null) === 'invalid_status_for_post_issue'
                && ($item['status'] ?? null) === 'draft';
        });
        $this->assertNotNull($draftEntry);
        $this->assertContains('draft_requires_issue', $draftEntry['reasons']);
    }

    private function seedVoucherInfrastructure(): void
    {
        $this->lqiType = VoucherType::factory()->create([
            'name' => 'Liquidación Inquilino X',
            'short_name' => 'LQI',
            'letter' => 'X',
            'credit' => false,
            'affects_account' => true,
            'affects_cash' => false,
        ]);

        $this->creditType = VoucherType::factory()->create([
            'name' => 'Nota de Crédito X',
            'short_name' => 'N/C',
            'letter' => 'X',
            'credit' => true,
            'affects_account' => true,
            'affects_cash' => false,
        ]);

        $this->debitType = VoucherType::factory()->create([
            'name' => 'Nota de Débito X',
            'short_name' => 'N/D',
            'letter' => 'X',
            'credit' => false,
            'affects_account' => true,
            'affects_cash' => false,
        ]);

        $this->lqiBooklet = Booklet::factory()->create([
            'name' => 'LQI Talonario',
            'voucher_type_id' => $this->lqiType->id,
            'sale_point_id' => SalePoint::factory()->create(['number' => 100])->id,
            'default_currency' => 'ARS',
            'next_number' => 1,
            'default' => true,
        ]);

        $this->creditBooklet = Booklet::factory()->create([
            'name' => 'NC Talonario',
            'voucher_type_id' => $this->creditType->id,
            'sale_point_id' => SalePoint::factory()->create(['number' => 101])->id,
            'default_currency' => 'ARS',
            'next_number' => 1,
        ]);

        $this->debitBooklet = Booklet::factory()->create([
            'name' => 'ND Talonario',
            'voucher_type_id' => $this->debitType->id,
            'sale_point_id' => SalePoint::factory()->create(['number' => 102])->id,
            'default_currency' => 'ARS',
            'next_number' => 1,
        ]);
    }

    private function seedChargeTypes(): void
    {
        $this->rentChargeType = ChargeType::create([
            'code' => ChargeType::CODE_RENT,
            'name' => 'Alquiler',
            'tenant_impact' => ChargeImpact::ADD,
            'owner_impact' => ChargeImpact::ADD,
            'currency_policy' => ChargeType::CURR_CONTRACT,
            'is_active' => true,
        ]);

        $this->addChargeType = ChargeType::create([
            'code' => 'POST_ADD',
            'name' => 'Ajuste Positivo',
            'tenant_impact' => ChargeImpact::ADD,
            'owner_impact' => ChargeImpact::ADD,
            'currency_policy' => ChargeType::CURR_CONTRACT,
            'is_active' => true,
        ]);

        $this->subtractChargeType = ChargeType::create([
            'code' => 'POST_SUB',
            'name' => 'Ajuste Negativo',
            'tenant_impact' => ChargeImpact::SUBTRACT,
            'owner_impact' => ChargeImpact::SUBTRACT,
            'currency_policy' => ChargeType::CURR_CONTRACT,
            'is_active' => true,
        ]);
    }

    private function createContractSetup(bool $withLqi = true): array
    {
        $periodDate = Carbon::create(2025, 1, 1);

        $contract = Contract::factory()->create([
            'currency' => 'ARS',
            'collection_booklet_id' => $this->debitBooklet->id,
            'settlement_booklet_id' => $this->lqiBooklet->id,
        ]);

        $client = Client::factory()->create();

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        $lqiVoucher = null;
        if ($withLqi) {
            $lqiVoucher = $this->createIssuedLqi($contract, $client, $periodDate, 'ARS');
        }

        return [$contract, $client, $periodDate, $lqiVoucher];
    }

    private function createIssuedLqi(Contract $contract, Client $client, Carbon $period, string $currency): Voucher
    {
        return Voucher::create([
            'booklet_id' => $this->lqiBooklet->id,
            'voucher_type_id' => $this->lqiType->id,
            'voucher_type_short_name' => 'LQI',
            'voucher_type_letter' => $this->lqiType->letter,
            'sale_point_number' => $this->lqiBooklet->salePoint->number,
            'number' => 1,
            'issue_date' => $period->copy()->endOfMonth()->toDateString(),
            'due_date' => $period->copy()->endOfMonth()->toDateString(),
            'period' => $period->toDateString(),
            'generated_from_collection' => false,
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_address' => $client->address,
            'client_document_type_name' => $client->document_type_name,
            'client_document_number' => $client->document_number,
            'client_tax_condition_name' => $client->tax_condition_name,
            'client_tax_id_number' => $client->tax_id_number,
            'contract_id' => $contract->id,
            'status' => VoucherStatus::Issued->value,
            'currency' => $currency,
            'subtotal_taxed' => 1000,
            'subtotal' => 1000,
            'total' => 1000,
        ]);
    }

    private function seedRentCharge(
        Contract $contract,
        Carbon $period,
        string $currency,
        ?int $voucherId = null,
        bool $settled = true
    ): ContractCharge {
        return ContractCharge::create([
            'contract_id' => $contract->id,
            'charge_type_id' => $this->rentChargeType->id,
            'amount' => 1000,
            'currency' => $currency,
            'effective_date' => $period->copy()->addDays(1)->toDateString(),
            'due_date' => $period->copy()->addDays(10)->toDateString(),
            'tenant_liquidation_voucher_id' => $voucherId,
            'tenant_liquidation_settled_at' => $settled ? now() : null,
            'is_canceled' => false,
        ]);
    }

    private function createCharge(
        Contract $contract,
        ChargeType $type,
        float $amount,
        Carbon $period,
        string $currency
    ): ContractCharge {
        return ContractCharge::create([
            'contract_id' => $contract->id,
            'charge_type_id' => $type->id,
            'amount' => $amount,
            'currency' => $currency,
            'effective_date' => $period->copy()->addDays(5)->toDateString(),
            'due_date' => $period->copy()->addDays(15)->toDateString(),
            'tenant_liquidation_voucher_id' => null,
            'tenant_liquidation_settled_at' => null,
            'is_canceled' => false,
        ]);
    }
}
