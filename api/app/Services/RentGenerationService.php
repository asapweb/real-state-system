<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ChargeType;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Enums\VoucherStatus; // si no usás enum, compará strings
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

class RentGenerationService
{
    public function __construct(
        private ContractRentCalculator $rentCalculator,
        // private VoucherSyncService $voucherSyncService,
        // private ContractAdjustmentService $adjustmentService
    ) {
    }

    /** Genera/asegura la renta de TODOS los contratos activos en el mes. */
    public function generateForMonth(Carbon $period, bool $dryRun = false): array
    {
        $processed = $created = $updated = $skipped = $errors = 0;
        $errorDetails = [];
        $skippedDetails = [];

        Contract::query()
            ->activeDuring($period)
            ->chunkById(200, function ($contracts) use ($period, $dryRun, &$processed, &$created, &$updated, &$skipped, &$errors, &$errorDetails, &$skippedDetails) {
                foreach ($contracts as $contract) {
                    $processed++;

                    try {
                        // (opcional) si tenés lógica de ajustes bloqueantes
                        if (method_exists($this, 'hasBlockingAdjustmentForPeriod') && $this->hasBlockingAdjustmentForPeriod($contract, $period)) {
                            $skipped++;
                            $skippedDetails[] = [
                                'contract_id' => $contract->id,
                                'reason'      => 'blocking_adjustment',
                                'message'     => 'El contrato tiene un ajuste bloqueante para el período.',
                            ];
                            continue;
                        }

                        if ($dryRun) {
                            $base   = $this->rentCalculator->monthlyBaseFor($contract, $period);
                            $amount = $this->rentCalculator->applyProrationIfNeeded($contract, $period, $base);
                            $skipped++; // solo preview
                            continue;
                        }

                        $result = $this->ensureRentCharge($contract, $period);
                        $created += (int) ($result['created'] ?? 0);
                        $updated += (int) ($result['updated'] ?? 0);
                        $skipped += (int) ($result['skipped'] ?? 0);

                    } catch (Throwable $e) {
                        $errors++;
                        $errorDetails[] = [
                            'contract_id' => $contract->id,
                            'message'     => $e->getMessage(),
                            'exception'   => (new \ReflectionClass($e))->getShortName(),
                        ];
                    }
                }
            });

        return [
            'processed'       => $processed,
            'created'         => $created,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'errors'          => $errors,
            'error_details'   => $errorDetails,
            'skipped_details' => $skippedDetails,
        ];
    }

    /** Genera/asegura la renta de UN contrato en el mes. */
    public function generateForContract(Contract $contract, Carbon $period, bool $dryRun = false): array
    {
        // (opcional) aplicar ajustes específicos
        // if (isset($this->adjustmentService)) {
        //     $this->adjustmentService->applyForPeriod($period, $contract);
        // }

        if ($dryRun) {
            $base   = $this->rentCalculator->monthlyBaseFor($contract, $period);
            $amount = $this->rentCalculator->applyProrationIfNeeded($contract, $period, $base);
            return [
                'processed' => 1,
                'created'   => 0,
                'updated'   => 0,
                'skipped'   => 1,
                'errors'    => 0,
                'preview'   => ['amount' => $amount, 'currency' => $contract->currency],
            ];
        }

        $result = $this->ensureRentCharge($contract, $period);

        return [
            'processed' => 1,
            'created'   => (int) ($result['created'] ?? 0),
            'updated'   => (int) ($result['updated'] ?? 0),
            'skipped'   => (int) ($result['skipped'] ?? 0),
            'errors'    => 0,
        ];
    }

    /**
     * Asegura 1 RENT por (contract, period, currency).
     * Si el RENT tiene voucher NO-DRAFT => no se toca (correcciones por NC/ajuste).
     * Si no tiene voucher o está en DRAFT => se crea/actualiza.
     */
    public function ensureRentCharge(Contract $contract, Carbon $period): array
    {
        return DB::transaction(function () use ($contract, $period) {
            // 1) calcular monto
            $base   = $this->rentCalculator->monthlyBaseFor($contract, $period);
            $amount = $this->rentCalculator->applyProrationIfNeeded($contract, $period, $base);
            $amount = abs($amount); // monto siempre positivo

            $effectiveDate = $period->copy()->startOfMonth()->toDateString();
            $currency      = $contract->currency;

            $rentTypeId = ChargeType::where('code', 'RENT')->value('id');
            if (!$rentTypeId) {
                throw new \RuntimeException('ChargeType code RENT no encontrado');
            }

            // 2) localizar existente (lock para evitar duplicados concurrentes)
            $charge = ContractCharge::query()
                ->where('contract_id', $contract->id)
                ->whereDate('effective_date', $effectiveDate)
                ->where('currency', $currency)
                ->where('charge_type_id', $rentTypeId)
                ->lockForUpdate()
                ->first();

            // 3) crear si no existe
            if (!$charge) {
                try {
                    ContractCharge::create([
                        'contract_id'       => $contract->id,
                        'charge_type_id'    => $rentTypeId,
                        'description'       => 'Monthly Rent',
                        'amount'            => $amount,                 // positivo
                        'currency'          => $currency,
                        'effective_date'    => $period->copy()->startOfMonth(),
                        'due_date'          => $this->defaultDueDate($contract, $period),
                        // nada de status/paid_by/responsible_party
                    ]);
                } catch (QueryException $e) {
                    if ($this->isDuplicateKey($e)) {
                        // si se insertó en paralelo, consideramos skip
                        return ['created' => 0, 'updated' => 0, 'skipped' => 1];
                    }
                    throw $e;
                }
                return ['created' => 1, 'updated' => 0, 'skipped' => 0];
            }

            // 4) existe: si tiene voucher NO-DRAFT => no tocar
            if ($charge->voucher_id) {
                $voucher = Voucher::find($charge->voucher_id);
                $isDraft = false;

                if ($voucher) {
                    // si usás enum:
                    if (class_exists(VoucherStatus::class)) {
                        $isDraft = ($voucher->status === VoucherStatus::DRAFT || $voucher->status?->value === 'DRAFT');
                    } else {
                        // fallback por string
                        $isDraft = (strtoupper((string) $voucher->status) === 'DRAFT');
                    }
                }

                if (!$isDraft) {
                    // ya documentado: no modificar la línea de renta
                    return ['created' => 0, 'updated' => 0, 'skipped' => 1];
                }
            }

            // 5) sin voucher o con voucher DRAFT => actualizar monto/vencimiento si cambió
            $changed = (round((float) $charge->amount, 2) !== round($amount, 2))
                    || ($charge->due_date?->toDateString() !== $this->defaultDueDate($contract, $period)->toDateString());

            if ($changed) {
                $charge->amount   = $amount;
                $charge->due_date = $this->defaultDueDate($contract, $period);
                $charge->save();

                // (opcional) sincronizar línea del voucher en DRAFT
                // if (isset($voucher) && $isDraft && isset($this->voucherSyncService)) {
                //     $this->voucherSyncService->syncChargeLine($voucher, $charge, [
                //         'description' => 'Monthly Rent',
                //         'amount'      => $amount,
                //         'currency'    => $currency,
                //         'type'        => 'rent',
                //     ]);
                // }

                return ['created' => 0, 'updated' => 1, 'skipped' => 0];
            }

            return ['created' => 0, 'updated' => 0, 'skipped' => 1];
        });
    }

    protected function defaultDueDate(Contract $contract, Carbon $period): Carbon
    {
        // Política simple: día 10 del mes del período
        return $period->copy()->startOfMonth()->day(10);
    }

    protected function isDuplicateKey(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'Duplicate entry') || str_contains($msg, 'UNIQUE constraint');
    }

    // (Opcional) solo si tenés este flujo implementado
    protected function hasBlockingAdjustmentForPeriod(Contract $contract, Carbon $period): bool
    {
        if (!method_exists($contract, 'adjustments')) return false;
        return $contract->adjustments()->blockingForPeriod($period)->exists();
    }
}
