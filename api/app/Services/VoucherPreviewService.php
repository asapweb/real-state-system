<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\ContractExpense;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class VoucherPreviewService
{
    public function getPreviewFor(Contract $contract, string $period): Collection
    {
        $date = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        $existingVoucher = $contract->vouchers()
            ->where('period', $period)
            ->whereHas('voucherType', fn ($q) => $q->where('short_name', 'COB'))
            ->with('items')
            ->first();

        $existingItems = $existingVoucher?->items ?? collect();

        // Contract expenses no incluidos en ninguna cobranza
        $expenses = ContractExpense::where('contract_id', $contract->id)
            ->where('period', $period)
            ->where('included_in_collection', true)
            ->whereNull('voucher_id')
            ->get()
            ->map(function ($expense) {
                return new VoucherItem([
                    'type' => 'expense',
                    'description' => $expense->description,
                    'quantity' => 1,
                    'unit_price' => $expense->amount,
                    'subtotal' => $expense->amount,
                    'vat_amount' => 0,
                    'subtotal_with_vat' => $expense->amount,
                    'meta' => ['source' => 'contract_expense', 'id' => $expense->id],
                ]);
            });

        // Si no hay item de tipo rent en el voucher existente, lo calculamos
        $hasRent = $existingItems->contains(fn($item) => $item->type === 'expense-rent');
        $rentItem = collect();

        if (! $hasRent) {
            $rentAmount = $contract->calculateRentForPeriod($date);

            $rentItem = collect([
                new VoucherItem([
                    'type' => 'rent',
                    'description' => 'Alquiler correspondiente a ' . $date->format('F Y'),
                    'quantity' => 1,
                    'unit_price' => $rentAmount,
                    'subtotal' => $rentAmount,
                    'vat_amount' => 0,
                    'subtotal_with_vat' => $rentAmount,
                    'meta' => ['source' => 'calculated'],
                ])
            ]);
        }

        return $existingItems->concat($expenses)->concat($rentItem);
    }
}
