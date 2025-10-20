<?php

namespace Tests\Feature\Voucher;

use App\Enums\VoucherStatus;
use App\Models\Booklet;
use App\Models\Client;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VoucherCancellationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function test_cancel_draft_voucher_returns_expected_payload(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $user->assignRole('admin');
        $this->actingAs($user);

        $voucher = $this->createCancelableVoucher();

        $response = $this->postJson("/api/vouchers/{$voucher->id}/cancel", [
            'reason' => 'Cancel voucher for testing',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'already_cancelled',
                    'reversed_account_movement_id',
                    'unset_charges_count',
                    'warnings',
                ],
            ]);

        $voucher->refresh();

        $this->assertSame(VoucherStatus::Cancelled, $voucher->status);
        $this->assertFalse($response->json('data.already_cancelled'));
        $this->assertSame(0, $response->json('data.unset_charges_count'));
        $this->assertSame(null, $response->json('data.reversed_account_movement_id'));
        $this->assertIsArray($response->json('data.warnings'));
    }

    private function createCancelableVoucher(array $overrides = []): Voucher
    {
        $voucherType = VoucherType::factory()->create([
            'name' => 'Liquidación Inquilino',
            'short_name' => 'LQI',
            'letter' => 'X',
            'affects_account' => true,
            'affects_cash' => false,
        ]);

        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        $client = Client::factory()->create();

        $defaults = [
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'voucher_type_short_name' => $voucherType->short_name,
            'voucher_type_letter' => $voucherType->letter,
            'sale_point_number' => 1,
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'client_id' => $client->id,
            'client_name' => $client->name,
            'currency' => 'ARS',
            'status' => VoucherStatus::Draft->value,
            'total' => 0,
            'subtotal_taxed' => 0,
            'subtotal_vat' => 0,
            'subtotal_exempt' => 0,
            'subtotal_untaxed' => 0,
            'subtotal_other_taxes' => 0,
            'generated_from_collection' => false,
            'afip_operation_type_id' => null,
        ];

        return Voucher::create(array_merge($defaults, $overrides));
    }
}
