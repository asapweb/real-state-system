<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\Booklet;
use App\Enums\VoucherItemType;
use App\Enums\CollectionItemType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CollectionMigrationService
{
    /**
     * Migra todas las collections existentes a vouchers
     */
    public function migrateAllCollections(): array
    {
        $results = [
            'migrated' => 0,
            'errors' => [],
            'skipped' => 0
        ];

        // Obtener un booklet compatible con COB
        $booklet = Booklet::whereHas('voucherType', function ($query) {
            $query->where('code', 'COB');
        })->first();

        if (!$booklet) {
            throw new \Exception('No se encontró un booklet compatible con tipo COB');
        }

        Collection::with(['items', 'client', 'contract'])->chunk(100, function ($collections) use ($booklet, &$results) {
            foreach ($collections as $collection) {
                try {
                    $this->migrateCollection($collection, $booklet);
                    $results['migrated']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'collection_id' => $collection->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Migra una collection específica a voucher
     */
    public function migrateCollection(Collection $collection, Booklet $booklet): Voucher
    {
        return DB::transaction(function () use ($collection, $booklet) {
            // Crear el voucher
            $voucher = Voucher::create([
                'booklet_id' => $booklet->id,
                'number' => $this->generateVoucherNumber($booklet),
                'issue_date' => $collection->issue_date,
                'period' => $collection->period,
                'due_date' => $collection->due_date,
                'client_id' => $collection->client_id,
                'contract_id' => $collection->contract_id,
                'status' => $this->mapStatus($collection->status),
                'currency' => $collection->currency,
                'total' => $collection->total_amount,
                'notes' => $collection->notes,
                'meta' => [
                    'migrated_from_collection_id' => $collection->id,
                    'paid_at' => $collection->paid_at,
                    'paid_by_user_id' => $collection->paid_by_user_id,
                ],
            ]);

            // Migrar los items
            foreach ($collection->items as $item) {
                $this->migrateCollectionItem($item, $voucher);
            }

            return $voucher;
        });
    }

    /**
     * Migra un CollectionItem a VoucherItem
     */
    private function migrateCollectionItem($collectionItem, Voucher $voucher): VoucherItem
    {
        return VoucherItem::create([
            'voucher_id' => $voucher->id,
            'type' => $this->mapItemType($collectionItem->type),
            'description' => $collectionItem->description,
            'quantity' => $collectionItem->quantity,
            'unit_price' => $collectionItem->unit_price,
            'subtotal' => $collectionItem->amount,
            'meta' => $collectionItem->meta,
        ]);
    }

    /**
     * Mapea el status de collection a voucher
     */
    private function mapStatus(string $collectionStatus): string
    {
        return match ($collectionStatus) {
            'pending' => 'draft',
            'paid' => 'issued',
            'canceled' => 'canceled',
            default => 'draft'
        };
    }

    /**
     * Mapea el tipo de CollectionItemType a VoucherItemType
     */
    private function mapItemType(CollectionItemType $type): VoucherItemType
    {
        return match ($type) {
            CollectionItemType::RENT => VoucherItemType::RENT,
            CollectionItemType::INSURANCE => VoucherItemType::INSURANCE,
            CollectionItemType::COMMISSION => VoucherItemType::COMMISSION,
            CollectionItemType::SERVICE => VoucherItemType::SERVICE,
            CollectionItemType::PENALTY => VoucherItemType::PENALTY,
            CollectionItemType::PRODUCT => VoucherItemType::PRODUCT,
            CollectionItemType::ADJUSTMENT => VoucherItemType::ADJUSTMENT,
            CollectionItemType::LATE_FEE => VoucherItemType::LATE_FEE,
        };
    }

    /**
     * Genera un número de voucher único
     */
    private function generateVoucherNumber(Booklet $booklet): string
    {
        $lastVoucher = Voucher::where('booklet_id', $booklet->id)
            ->orderBy('number', 'desc')
            ->first();

        $lastNumber = $lastVoucher ? (int) $lastVoucher->number : 0;
        return str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
    }
}
