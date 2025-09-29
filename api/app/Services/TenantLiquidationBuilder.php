<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCharge;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TenantLiquidationBuilder
{
    public function buildForPeriod(Contract $contract, Carbon $period): Voucher
    {
        return DB::transaction(function () use ($contract, $period) {

            // 1) traer cargos elegibles
            $charges = ContractCharge::query()
                ->forContract($contract->id)
                ->active()
                ->with('chargeType')
                ->get()
                ->filter(fn(ContractCharge $c) => $c->shouldIncludeInTenantLiquidation($period));

            // 2) obtener o crear la LIQ DRAFT del período (ajustá a tu modelo de Voucher)
            $voucher = $this->getOrCreateTenantDraftVoucher($contract, $period);

            // 3) sincronizar líneas (pseudocódigo; adaptá a tu schema de voucher lines)
            foreach ($charges as $charge) {
                $this->upsertVoucherLine($voucher, $charge, [
                    'description' => $charge->description ?: $charge->chargeType->name,
                    'amount'      => $charge->signedAmountForTenant(), // aplica signo
                    'currency'    => $charge->currency,
                    'effective_date' => $charge->effective_date?->toDateString(),
                ]);

                // (opcional) además podés pre-vincular el cargo al voucher DRAFT:
                $charge->tenant_liquidation_voucher_id = $voucher->id;
                $charge->save();
            }

            // 4) recalcular totales del voucher (si corresponde)
            $this->recalculateVoucherTotals($voucher);

            return $voucher->fresh(); // con líneas
        });
    }

    private function getOrCreateTenantDraftVoucher(Contract $contract, Carbon $period): Voucher
    {
        // Pseudocódigo: buscá LIQ DRAFT del tenant para ese contrato+mes; si no, crear
        // return Voucher::firstOrCreate([...], [...]);
        return new Voucher; // reemplazar por tu implementación
    }

    private function upsertVoucherLine(Voucher $voucher, ContractCharge $charge, array $payload): void
    {
        // Pseudocódigo: si existe línea por charge_id => update; si no => create
        // $voucher->lines()->updateOrCreate(['contract_charge_id' => $charge->id], $payload);
    }

    private function recalculateVoucherTotals(Voucher $voucher): void
    {
        // Pseudocódigo: sumar líneas y setear totales/moneda
    }
}
