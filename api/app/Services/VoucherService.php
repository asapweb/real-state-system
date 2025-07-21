<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\AccountMovement;
use App\Models\CashMovement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\VoucherValidatorService;
use Exception;

class VoucherService
{
    public function __construct(
        protected VoucherValidatorService $validatorService,
    ) {}

    public function issue(Voucher $voucher): Voucher
    {
        return DB::transaction(function () use ($voucher) {
            if ($voucher->status !== 'draft') {
                throw new Exception('Solo se pueden emitir vouchers en estado draft.');
            }

            $type = $voucher->booklet?->voucherType;

            // Precarga de relaciones si es necesario
            if ($type?->affects_cash && $voucher->payments()->exists()) {
                $voucher->loadMissing('payments.paymentMethod');
            }

            $voucher->number = $voucher->booklet->generateNextNumber();

            // ✅ Validación funcional por tipo (RCB, NC, FAC...) con lógica completa
            $this->validatorService->validateBeforeIssue($voucher);
            $voucher->status = 'issued';
            $voucher->save();

            $sign = $type->credit ? 1 : -1;
            $date = $voucher->issue_date ?? now();

            // Movimiento en cuenta corriente
            if ($type?->affects_account && $voucher->client_id) {
                $alreadyExists = AccountMovement::where('voucher_id', $voucher->id)->exists();

                if (! $alreadyExists) {
                    AccountMovement::create([
                        'client_id' => $voucher->client_id,
                        'voucher_id' => $voucher->id,
                        'date' => $date,
                        'description' => $type->name . ' ' . $voucher->number,
                        'amount' => $sign * $voucher->total,
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
                                'cash_account_id' => $payment->cash_account_id,
                                'voucher_id' => $voucher->id,
                                'payment_method_id' => $payment->payment_method_id,
                                'date' => $date,
                                'amount' => $sign * $payment->amount,
                                'currency' => $voucher->currency,
                                'reference' => $payment->reference,
                            ]);
                        }
                    }
                }
            }

            Log::info('Voucher emitido', [
                'voucher_id' => $voucher->id,
                'tipo' => $type?->short_name,
                'afecta_cuenta' => $type?->affects_account,
                'afecta_caja' => $type?->affects_cash,
            ]);

            return $voucher;
        });
    }
}
