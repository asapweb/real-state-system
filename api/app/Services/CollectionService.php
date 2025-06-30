<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Enums\CollectionItemType;
use App\Models\Contract;
use Carbon\Carbon;

class CollectionService
{
    /**
     * Marca una cobranza como pagada, registra la fecha, el usuario y aplica punitorio por mora si corresponde.
     *
     * Este método debe ser llamado únicamente como resultado de una acción explícita y validada:
     * - Pago manual registrado desde el sistema
     * - Confirmación vía integración externa (por ejemplo, webhook de MercadoPago)
     * - Validación administrativa
     *
     * Registra además qué usuario realizó la acción, si hay uno autenticado.
     *
     * @param Collection $collection   La cobranza a marcar como pagada
     * @param Carbon $paymentDate      La fecha efectiva del pago (por defecto: ahora)
     * @return void
     */
    public function markAsPaid(Collection $collection, Carbon $paymentDate, ?int $userId = null): void
    {
        $collection->update([
            'status' => 'paid',
            'paid_at' => $paymentDate,
            'paid_by_user_id' => $userId,
        ]);

        $this->applyLateFee($collection, $paymentDate);
        $this->recalculateTotal($collection);
    }


    /**
     * Aplica un punitorio por mora si el pago fue fuera de término.
     *
     * @param Collection $collection
     * @param Carbon $paymentDate
     * @return void
     */
    public function applyLateFee(Collection $collection, Carbon $paymentDate): void
    {
        $contract = $collection->contract;
        if (!$contract || !$contract->has_penalty) {
            return;
        }

        $period = Carbon::createFromFormat('Y-m', $collection->period)->startOfMonth();
        $lateFee = $contract->calculateLateFee($period, $paymentDate);

        if (!$lateFee || $lateFee['amount'] <= 0) {
            return;
        }

        CollectionItem::create([
            'collection_id' => $collection->id,
            'type' => CollectionItemType::LateFee,
            'description' => 'Interés por mora del ' . $lateFee['due_date'] . ' al ' . $lateFee['payment_date'],
            'quantity' => 1,
            'unit_price' => $lateFee['amount'],
            'amount' => $lateFee['amount'],
            'currency' => $collection->currency,
            'meta' => $lateFee,
        ]);
    }

    /**
     * Recalcula el total de una cobranza sumando todos sus ítems.
     *
     * @param Collection $collection
     * @return void
     */
    public function recalculateTotal(Collection $collection): void
    {
        $total = $collection->items()->sum('amount');
        $collection->update(['total_amount' => $total]);
    }
}
