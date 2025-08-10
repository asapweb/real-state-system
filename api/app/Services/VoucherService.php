<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\PaymentMethod;
use App\Models\AccountMovement;
use App\Models\VoucherApplication;
use App\Models\VoucherAssociation;
use App\Models\VoucherPayment;
use App\Models\CashAccount;
use App\Models\CashMovement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\VoucherValidatorService;
use Exception;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreVoucherRequest;
use Illuminate\Support\Facades\Validator;


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

                            // throw new Exception('MomentitooooO!!!! ' . $voucher->booklet->voucherType->credit);
                        if (! $exists) {
                            CashMovement::create([
                                'direction' => $voucher->booklet->voucherType->credit ? 'in' : 'out',
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

    public function createFromArray(array $data): Voucher
    {
        $request = new StoreVoucherRequest();
        $request->replace($data); // ✅ Esto es clave

        $validator = Validator::make($data, $request->rules(), $request->messages());


        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        if (!($validatedData['generated_from_collection'] ?? false)) {
            foreach ($validatedData['items'] ?? [] as $item) {
                if (($item['type'] ?? null) === 'rent') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Los ítems de tipo renta solo pueden generarse desde el Editor de Cobranzas.',
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($validatedData) {
            $booklet = \App\Models\Booklet::findOrFail($validatedData['booklet_id']);

            // Obtener cliente (con validación especial para COB)
            if (($validatedData['voucher_type_short_name'] ?? $booklet->voucherType->short_name) === 'ALQ') {
                $contract = \App\Models\Contract::with('clients')->findOrFail($validatedData['contract_id']);
                $isTenant = $contract->clients()
                ->where('role', \App\Enums\ContractClientRole::TENANT)
                ->where('contract_clients.client_id', $validatedData['client_id'])
                ->exists();

                if (! $isTenant) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'client_id' => 'El cliente seleccionado no es inquilino del contrato indicado.',
                    ]);
                }
            }

            // Preparar datos
            $voucherData = $validatedData;
            $voucherData['booklet_id'] = $booklet->id;
            $voucherData['voucher_type_id'] = $booklet->voucher_type_id;
            $voucherData['voucher_type_short_name'] = $booklet->voucherType->short_name;
            $voucherData['voucher_type_letter'] = $booklet->voucherType->letter;
            $voucherData['sale_point_number'] = $booklet->salePoint->number;
            $voucherData['number'] = null; // Se asignará al emitir
            $voucherData['status'] = 'draft';
            $voucherData['service_date_from'] = $validatedData['service_date_from'] ?? null;
            $voucherData['service_date_to'] = $validatedData['service_date_to'] ?? null;
            $voucherData['afip_operation_type_id'] = $validatedData['afip_operation_type_id'] ?? null;

            $voucher = new \App\Models\Voucher($voucherData);

            // Set items (antes de calcular)
            if (!empty($validatedData['items'])) {
                $items = [];
                foreach ($validatedData['items'] as $itemData) {
                    $items[] = new \App\Models\VoucherItem($itemData);
                }
                $voucher->setRelation('items', collect($items));
            }

            // Set payments (antes de calcular si el tipo lo requiere)
            if (!empty($validatedData['payments'])) {
                $payments = [];
                foreach ($validatedData['payments'] as $paymentData) {
                    $payments[] = new \App\Models\VoucherPayment($paymentData);
                }
                $voucher->setRelation('payments', collect($payments));
            }

            // Cálculo de totales según tipo
            app(\App\Services\VoucherCalculationService::class)->calculateVoucher($voucher);

            $voucher->save();

            // Guardar items
            if ($voucher->relationLoaded('items')) {
                // throw \Illuminate\Validation\ValidationException::withMessages([
                //     'items' => $voucher->items,
                // ]);
                $voucher->items()->saveMany($voucher->items);
            }

            // Guardar payments
            if ($voucher->relationLoaded('payments')) {
                $voucher->payments()->saveMany($voucher->payments);
            }

            // Guardar applications (para RCB/RPG)
            if (!empty($validatedData['applications'])) {
                foreach ($validatedData['applications'] as $applicationData) {
                    $voucher->applications()->create([
                        'applied_to_id' => $applicationData['applied_to_id'],
                        'amount' => $applicationData['amount'],
                    ]);
                }
            }

            // Guardar voucher_associations (para NC/ND)
            if (!empty($validatedData['associated_voucher_ids'])) {
                foreach ($validatedData['associated_voucher_ids'] as $assocData) {
                    $voucher->voucherAssociations()->create([
                        'associated_voucher_id' => $assocData
                    ]);
                }
            }

            return $voucher;
        });
    }

    public function updateFromArray(Voucher $voucher, array $data): Voucher
    {
        \Log::info('************************************************');
        \Log::info('data', ['data', $data]);
        $request = new \App\Http\Requests\UpdateVoucherRequest();
        $request->replace($data);

        $validator = \Validator::make($data, $request->rules(), $request->messages());

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $validatedData = $validator->validated();

        if ($voucher->status !== 'draft') {
            throw new \Exception('Solo se pueden editar comprobantes en estado borrador.');
        }

        if (!($validatedData['generated_from_collection'] ?? false)) {
            foreach ($validatedData['items'] ?? [] as $item) {
                if (($item['type'] ?? null) === 'rent') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Los ítems de tipo renta solo pueden generarse desde el Editor de Cobranzas.',
                    ]);
                }
            }
        }

        return \DB::transaction(function () use ($voucher, $validatedData) {

            // Validación especial para ALQ
            $booklet = $voucher->booklet()->with('voucherType', 'salePoint')->firstOrFail();
            $voucherType = $booklet->voucherType;

            if ($voucherType->short_name === 'ALQ') {
                $contract = \App\Models\Contract::with('clients')->findOrFail($validatedData['contract_id']);
                $isTenant = $contract->clients()
                    ->where('role', \App\Enums\ContractClientRole::TENANT)
                    ->where('contract_clients.client_id', $validatedData['client_id'])
                    ->exists();

                if (! $isTenant) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'client_id' => 'El cliente seleccionado no es inquilino del contrato indicado.',
                    ]);
                }
            }

            // Actualizar campos editables
            $voucher->fill(collect($validatedData)->only([
                'issue_date',
                'due_date',
                'period',
                'contract_id',
                'client_id',
                'client_name',
                'client_address',
                'client_document_type_name',
                'client_document_number',
                'client_tax_condition_name',
                'client_tax_id_number',
                'currency',
                'notes',
                'meta',
                'afip_operation_type_id',
                'service_date_from',
                'service_date_to',
            ])->toArray());

            // Preparar ítems (no guardar aún)
            if (!empty($validatedData['items'])) {
                $voucher->items()->delete();
                $items = collect($validatedData['items'])->map(function ($itemData) {
                    if (!isset($itemData['type'])) {
                        $itemData['type'] = 'service';
                    }
                    return new \App\Models\VoucherItem($itemData);
                });
                $voucher->setRelation('items', $items);
            }

            \Log::info('pagos', ['pagos', $validatedData]);
            // Preparar pagos (no guardar aún)
            if (!empty($validatedData['payments'])) {
                $voucher->payments()->delete();
                $payments = collect($validatedData['payments'])->map(function ($paymentData) {
                    if (!isset($paymentData['payment_method_id'])) {
                        $default = \App\Models\PaymentMethod::where('is_default', true)->first();
                        if (!$default) {
                            $account = \App\Models\CashAccount::firstOrCreate([
                                'name' => 'Caja Principal',
                                'type' => 'cash',
                                'currency' => 'ARS',
                            ]);
                            $default = \App\Models\PaymentMethod::create([
                                'name' => 'Efectivo',
                                'is_default' => true,
                                'default_cash_account_id' => $account->id,
                            ]);
                        }
                        $paymentData['payment_method_id'] = $default->id;
                    }

                    return new \App\Models\VoucherPayment($paymentData);
                });
                $voucher->setRelation('payments', $payments);
            }

            // Calcular totales en base a ítems y pagos en memoria
            app(\App\Services\VoucherCalculationService::class)->calculateVoucher($voucher);

            // Guardar voucher principal
            $voucher->save();

            // Guardar ítems
            if ($voucher->relationLoaded('items')) {
                $voucher->items()->saveMany($voucher->items);
            }

            // Guardar pagos
            if ($voucher->relationLoaded('payments')) {
                $voucher->payments()->saveMany($voucher->payments);
            }

            // Guardar asociaciones
            if (!empty($validatedData['associated_voucher_ids'])) {
                $voucher->associations()->delete();
                foreach ($validatedData['associated_voucher_ids'] as $assocId) {
                    $voucher->voucherAssociations()->create([
                        'associated_voucher_id' => $assocId,
                    ]);
                }
            }

            // Guardar aplicaciones
            if (!empty($validatedData['applications'])) {
                $voucher->applications()->delete();
                foreach ($validatedData['applications'] as $appData) {
                    $voucher->applications()->create([
                        'applied_to_id' => $appData['applied_to_id'],
                        'amount' => $appData['amount'],
                    ]);
                }
            }

            return $voucher;
        });
    }

}
