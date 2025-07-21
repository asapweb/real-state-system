<?php

namespace Database\Factories;

use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'booklet_id' => 1,
            'voucher_type_id' => null, // se setea en withType()
            'voucher_type_short_name' => null,
            'voucher_type_letter' => null,
            'sale_point_number' => 1,
            'number' => $this->faker->unique()->randomNumber(8),
            'issue_date' => $this->faker->dateTimeThisDecade,
            'due_date' => $this->faker->dateTimeBetween('+1 days', '+2 months'),
            'service_date_from' => $this->faker->dateTimeThisDecade,
            'service_date_to' => $this->faker->dateTimeThisDecade,
            'period' => now()->format('Y-m'),
            'client_id' => Client::factory(),
            'client_name' => $this->faker->name,
            'client_address' => $this->faker->address,
            'client_document_type_name' => 'CUIT',
            'client_document_number' => (string) $this->faker->randomNumber(8),
            'client_tax_condition_name' => 'Responsable Inscripto',
            'client_tax_id_number' => (string) $this->faker->numberBetween(20000000000, 20999999999),
            'contract_id' => null, // opcional según tipo
            'afip_operation_type_id' => 1,
            'status' => 'draft',
            'currency' => 'ARS',
            'notes' => $this->faker->sentence,
            'meta' => [],
            'cae' => null,
            'cae_expires_at' => now()->addDays(10),
            'subtotal_taxed' => 1000,
            'subtotal_untaxed' => 0,
            'subtotal_exempt' => 0,
            'subtotal_vat' => 210,
            'subtotal_other_taxes' => 0,
            'total' => 1210,
        ];
    }

    // ---------------------------
    // Estados por tipo de comprobante
    // ---------------------------

    public function withType(string $shortName): static
    {
        return $this->state(function () use ($shortName) {
            $type = VoucherType::where('short_name', $shortName)->firstOrFail();

            return [
                'voucher_type_id' => $type->id,
                'voucher_type_short_name' => $type->short_name,
                'voucher_type_letter' => $type->letter,
            ];
        });
    }

    public function fac(): static
    {
        return $this->withType('FAC');
    }

    public function creditNote(): static
    {
        return $this->withType('N/C')->state([
            'total' => -1210,
            'subtotal_taxed' => -1000,
            'subtotal_vat' => -210,
        ]);
    }

    public function debitNote(): static
    {
        return $this->withType('N/D');
    }

    public function receipt(): static
    {
        return $this->withType('RCB');
    }

    public function payment(): static
    {
        return $this->withType('RPG');
    }

    public function cob(): static
    {
        return $this->withType('COB')->state([
            'contract_id' => Contract::factory(),
        ]);
    }

    public function liq(): static
    {
        return $this->withType('LIQ');
    }

    public function draft(): static
    {
        return $this->state([
            'status' => 'draft',
        ]);
    }
}
