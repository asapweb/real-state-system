<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\AccountMovement;
use App\Models\CashMovement;

class VoucherService
{
    public function issue(Voucher $voucher): Voucher
    {
        if ($voucher->status !== 'draft') {
            throw new \Exception('Solo se pueden emitir vouchers en estado draft.');
        }

        if ($voucher->payments()->count() > 0 && $voucher->booklet?->voucherType?->affects_cash) {
            $voucher->loadMissing('payments.paymentMethod');
        }

        $voucher->status = 'issued';
        $voucher->save();

        $type = $voucher->booklet?->voucherType;

        // Movimiento en cuenta corriente
        if ($type?->affects_account && $voucher->client_id) {
            $alreadyExists = AccountMovement::where('voucher_id', $voucher->id)->exists();
            if (! $alreadyExists) {
                AccountMovement::create([
                    'client_id' => $voucher->client_id,
                    'voucher_id' => $voucher->id,
                    'date' => $voucher->issue_date,
                    'description' => $type->name . ' ' . $voucher->number,
                    'amount' => $type->credit ? -$voucher->total : $voucher->total,
                    'currency' => $voucher->currency,
                    'is_initial' => false,
                ]);
            }
        }

        // Movimiento en caja
        if ($type?->affects_cash) {
            foreach ($voucher->payments as $payment) {
                if ($payment->paymentMethod?->handled_by_agency) {
                    $exists = CashMovement::where('voucher_id', $voucher->id)
                        ->where('payment_method_id', $payment->payment_method_id)
                        ->where('amount', $payment->amount)
                        ->exists();

                    if (! $exists) {
                        CashMovement::create([
                            'voucher_id' => $voucher->id,
                            'payment_method_id' => $payment->payment_method_id,
                            'date' => $voucher->issue_date,
                            'amount' => $type->credit ? -$payment->amount : $payment->amount,
                            'currency' => $voucher->currency,
                            'reference' => $payment->reference,
                        ]);
                    }
                }
            }
        }

        return $voucher;
    }
}
