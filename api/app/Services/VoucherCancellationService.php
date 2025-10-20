<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Exceptions\VoucherCancellationConflictException;
use App\Models\AccountMovement;
use App\Models\CashMovement;
use App\Models\ContractCharge;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VoucherCancellationService
{
    /**
     * Cancela un voucher de forma segura e idempotente.
     *
     * @return array{
     *   already_cancelled: bool,
     *   reversed_account_movement_id: ?int,
     *   unset_charges_count: int,
     *   warnings: string[]
     * }
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Exceptions\VoucherCancellationConflictException
     */
    public function cancel(int|Voucher $voucher, string $reason, User $actor): array
    {
        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'canceled_reason' => 'La razón de cancelación es obligatoria.',
            ]);
        }

        return DB::transaction(function () use ($voucher, $normalizedReason, $actor) {
            $voucherId = $voucher instanceof Voucher ? $voucher->getKey() : $voucher;

            /** @var Voucher $lockedVoucher */
            $lockedVoucher = Voucher::query()
                ->with('voucherType')
                ->whereKey($voucherId)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var VoucherStatus|null $statusBefore */
            $statusBefore = $lockedVoucher->status;

            // Idempotencia: si ya está cancelado devolvemos early.
            if ($this->isCancelled($lockedVoucher)) {
                return $this->buildResult(true, null, 0, []);
            }

            $type = $lockedVoucher->voucherType;
            if (!$type) {
                throw new VoucherCancellationConflictException(
                    'Voucher cannot be cancelled in current state',
                    ['missing_voucher_type']
                );
            }

            /**
             * Política de cancelación para emitidos NO-AFIP (afip_id = null):
             *  - Se permite cancelar si NO existen movimientos de caja y no hay vínculos (aplicaciones/pagos/asociaciones).
             *  - Regla de CAJA simplificada: si hay movimientos de caja, se bloquea SIEMPRE (independientemente de affects_cash).
             */
            if ($this->hasCashMovements($lockedVoucher)) {
                throw new VoucherCancellationConflictException(
                    'Voucher has cash movements',
                    ['cash_movements_present']
                );
            }

            // Draft → cancelar directo, sin reversa ni unset.
            if ($statusBefore === VoucherStatus::Draft) {
                $this->markCancelled($lockedVoucher, $normalizedReason, $actor);
                $this->logCancellation($lockedVoucher, $statusBefore, null, 0, []);
                // @todo: event(new \App\Events\VoucherCancelled($lockedVoucher));
                return $this->buildResult(false, null, 0, []);
            }

            // Solo soportamos cancelación para Issued en este flujo.
            if ($statusBefore !== VoucherStatus::Issued) {
                throw new VoucherCancellationConflictException(
                    'Voucher cannot be cancelled in current state',
                    ['unsupported_status']
                );
            }

            $shortName = strtoupper((string) $type->short_name);

// Soporte general: permitimos cancelar cualquier tipo emitido sin caja ni vínculos.
// Solo LQI/LQP realizan unset de cargos; el resto no desasocia cargos.
$supportsUnset = in_array($shortName, ['LQI', 'LQP'], true);

// No se puede cancelar si hay aplicaciones/pagos/asociaciones vinculadas.
            if ($this->hasBlockingLinks($lockedVoucher)) {
                throw new VoucherCancellationConflictException(
                    'Voucher has applications/payments linked',
                    ['linked_records_present']
                );
            }

            $reversedId = null;
$warnings = [];

// Reversa contable si el tipo afecta cuenta corriente.
if ($type->affects_account) {
    $reversedId = $this->createReversalMovement($lockedVoucher, $actor, $warnings);
}

// Desasociar cargos solo para LQI/LQP.
$unsetCharges = 0;
if ($supportsUnset) {
    $unsetCharges = $this->unsetCharges($lockedVoucher, $shortName);
    if ($unsetCharges === 0) {
        $warnings[] = 'no_charges_unset';
    }
}

// Marcar voucher cancelado.
$this->markCancelled($lockedVoucher, $normalizedReason, $actor);

            // Log + eventos (pendientes).
            $this->logCancellation($lockedVoucher, $statusBefore, $reversedId, $unsetCharges, $warnings);
            // @todo: event(new \App\Events\ChargesUnsettled($lockedVoucher->id, $unsetCharges));
            // @todo: event(new \App\Events\AccountMovementReversed($reversedId));
            // @todo: event(new \App\Events\VoucherCancelled($lockedVoucher));

            return $this->buildResult(false, $reversedId, $unsetCharges, $warnings);
        });
    }

    private function isCancelled(Voucher $voucher): bool
    {
        return $voucher->status === VoucherStatus::Cancelled;
    }

    private function hasCashMovements(Voucher $voucher): bool
    {
        return CashMovement::query()
            ->where('voucher_id', $voucher->getKey())
            ->exists();
    }

    private function accountMovementExists(Voucher $voucher): bool
    {
        return AccountMovement::query()
            ->where('voucher_id', $voucher->getKey())
            ->exists();
    }

    private function hasBlockingLinks(Voucher $voucher): bool
    {
        return $voucher->applications()->exists()
            || $voucher->applicationsReceived()->exists()
            || $voucher->payments()->exists()
            || $voucher->associations()->exists()
            || $voucher->associatedBy()->exists();
    }

    /**
     * Crea una reversa del último movimiento contable vinculado al voucher.
     * Si no existe, añade warning y no falla.
     */
    private function createReversalMovement(Voucher $voucher, User $actor, array &$warnings): ?int
    {
        $originalMovement = AccountMovement::query()
            ->where('voucher_id', $voucher->getKey())
            ->orderByDesc('id')
            ->first();

        if (!$originalMovement) {
            $warnings[] = 'account_movement_missing';
            Log::warning('Voucher cancellation: account movement missing for reversal', [
                'voucher_id' => $voucher->getKey(),
            ]);
            return null;
        }

        $descriptionSuffix = $voucher->full_number ?? ('#' . $voucher->getKey());

        $reversal = AccountMovement::create([
            'client_id'  => $originalMovement->client_id,
            'voucher_id' => $voucher->getKey(),
            'date'       => now(),
            'description'=> 'Reversa cancelación voucher ' . $descriptionSuffix,
            'amount'     => $originalMovement->amount * -1,
            'currency'   => $originalMovement->currency,
            'is_initial' => false,
            'meta'       => array_merge(
                (array) $originalMovement->meta,
                [
                    'is_reversal'                 => true,
                    'is_reversal_of_movement_id'  => $originalMovement->id,
                    'reversal_reason'             => 'voucher_cancelled',
                    'reversal_actor_id'           => $actor->getKey(),
                ]
            ),
        ]);

        $originalMovement->meta = array_merge(
            (array) $originalMovement->meta,
            [
                'reversed_by_movement_id' => $reversal->id,
                'reversal_recorded_at'    => now()->toIso8601String(),
            ]
        );
        $originalMovement->save();

        return $reversal->id;
    }

    private function unsetCharges(Voucher $voucher, string $shortName): int
    {
        if ($shortName === 'LQI') {
            return ContractCharge::query()
                ->where('tenant_liquidation_voucher_id', $voucher->getKey())
                ->update([
                    'tenant_liquidation_voucher_id' => null,
                    'tenant_liquidation_settled_at' => null,
                ]);
        }

        // LQP
        return ContractCharge::query()
            ->where('owner_liquidation_voucher_id', $voucher->getKey())
            ->update([
                'owner_liquidation_voucher_id' => null,
                'owner_liquidation_settled_at' => null,
            ]);
    }

    private function markCancelled(Voucher $voucher, string $reason, User $actor): void
    {
        $voucher->forceFill([
            'status'          => VoucherStatus::Cancelled,
            'canceled_at'     => now(),
            'canceled_by'     => $actor->getKey(),
            'canceled_reason' => $reason,
        ]);

        $voucher->save();
    }

    private function logCancellation(
        Voucher $voucher,
        VoucherStatus $statusBefore,
        ?int $reversedId,
        int $unsetCount,
        array $warnings
    ): void {
        Log::info('Voucher cancellation executed', [
            'voucher_id'    => $voucher->getKey(),
            'type_short'    => strtoupper((string) $voucher->voucherType?->short_name),
            'status_before' => $statusBefore->value,
            'status_after'  => ($voucher->status instanceof VoucherStatus) ? $voucher->status->value : $voucher->status,
            'reversed_id'   => $reversedId,
            'unset_count'   => $unsetCount,
            'warnings'      => $warnings,
        ]);
    }

    /**
     * @param string[] $warnings
     * @return array{
     *   already_cancelled: bool,
     *   reversed_account_movement_id: ?int,
     *   unset_charges_count: int,
     *   warnings: string[]
     * }
     */
    private function buildResult(bool $alreadyCancelled, ?int $reversedId, int $unsetChargesCount, array $warnings): array
    {
        return [
            'already_cancelled'            => $alreadyCancelled,
            'reversed_account_movement_id' => $reversedId,
            'unset_charges_count'          => $unsetChargesCount,
            'warnings'                     => $warnings,
        ];
    }
}
