<?php

namespace App\Models;

use App\Events\ContractChargeCanceled;
use App\Models\User;
use BackedEnum;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ContractCharge extends Model
{
    protected $fillable = [
        'contract_id',
        'charge_type_id',
        'service_type_id',
        'counterparty_contract_client_id',

        'amount',
        'currency',

        'effective_date',
        'due_date',
        'service_period_start',
        'service_period_end',
        'invoice_date',

        'tenant_liquidation_voucher_id',
        'owner_liquidation_voucher_id',

        // nombres alineados con la migración
        'tenant_liquidation_settled_at',
        'owner_liquidation_settled_at',

        'description',

        'canceled_at',
        'canceled_by',
        'canceled_reason',
        'is_canceled',
    ];

    protected $casts = [
        'amount'                          => 'decimal:2',
        'effective_date'                  => 'date',
        'due_date'                        => 'date',
        'service_period_start'            => 'date',
        'service_period_end'              => 'date',
        'invoice_date'                    => 'date',
        'tenant_liquidation_settled_at'   => 'datetime',
        'owner_liquidation_settled_at'    => 'datetime',
        // quitar requires_* porque no existen en la tabla
        'is_canceled'                     => 'bool',
        'canceled_at'                     => 'datetime',
    ];

    /* =========================
     * Relaciones
     * ========================= */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(ContractClient::class, 'counterparty_contract_client_id');
    }

    public function tenantLiquidationVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'tenant_liquidation_voucher_id');
    }

    public function ownerLiquidationVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'owner_liquidation_voucher_id');
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    /* =========================
     * Scopes
     * ========================= */
    public function scopeForContract($q, int $contractId)
    {
        return $q->where('contract_id', $contractId);
    }

    public function scopeEffectiveBetween($q, Carbon $from, Carbon $to)
    {
        return $q->whereDate('effective_date', '>=', $from->toDateString())
                 ->whereDate('effective_date', '<',  $to->toDateString());
    }

    public function scopeEffectiveInMonth($q, Carbon $period)
    {
        $from = $period->copy()->startOfMonth();
        $to   = $from->copy()->addMonth();
        return $this->scopeEffectiveBetween($q, $from, $to);
    }

    public function scopeWithType($q)
    {
        return $q->with('chargeType');
    }

    public function scopeActive($query)
    {
        return $query->where('is_canceled', false)
                     ->whereNull('canceled_at');
    }

    public function scopeCanceled($query)
    {
        return $query->whereNotNull('canceled_at')
                     ->where('is_canceled', true);
    }

    /* =========================
     * Helpers de impacto y elegibilidad
     * ========================= */
    public function shouldIncludeInTenantLiquidation(Carbon $period): bool
    {
        if ($this->is_canceled) return false;

        $type   = $this->getLoadedType();
        $impact = $type->tenant_impact->value ?? $type->tenant_impact;

        if (!in_array($impact, ['add','subtract'], true)) return false;
        if (!$this->isInPeriod($period)) return false;

        if (($type->requires_counterparty ?? null) === 'tenant' && !$this->counterparty_contract_client_id) return false;

        // usar nombre alineado
        if (!is_null($this->tenant_liquidation_settled_at)) return false;

        return true;
    }

    public function shouldIncludeInOwnerLiquidation(Carbon $period): bool
    {
        if ($this->is_canceled) return false;

        $type   = $this->getLoadedType();
        $impact = $type->owner_impact->value ?? $type->owner_impact;

        if (!in_array($impact, ['add','subtract'], true)) return false;
        if (!$this->isInPeriod($period)) return false;

        if (($type->requires_counterparty ?? null) === 'owner' && !$this->counterparty_contract_client_id) return false;

        if (!is_null($this->owner_liquidation_settled_at)) return false;

        return true;
    }

    public function signedAmountForTenant(): float
    {
        $type = $this->getLoadedType();
        return (float) $type->signedAmountForTenant((float) $this->amount);
    }

    public function signedAmountForOwner(): float
    {
        $type = $this->getLoadedType();
        return (float) $type->signedAmountForOwner((float) $this->amount);
    }

    public function markTenantSettled(?Carbon $at = null): void
    {
        $this->tenant_liquidation_settled_at = $at ?: now();
        $this->save();
    }

    public function markOwnerSettled(?Carbon $at = null): void
    {
        $this->owner_liquidation_settled_at = $at ?: now();
        $this->save();
    }

    public function reopenTenantSide(): void
    {
        $this->tenant_liquidation_settled_at = null;
        $this->save();
    }

    public function reopenOwnerSide(): void
    {
        $this->owner_liquidation_settled_at = null;
        $this->save();
    }

    public function cancel(string $reason, User $by): void
    {
        if ($this->is_canceled) {
            return;
        }

        if ($this->hasLockedLiquidationVoucher()) {
            throw new DomainException('has_non_draft_voucher');
        }

        $this->canceled_at     = now();
        $this->canceled_reason = $reason;
        $this->canceled_by     = $by->id;
        $this->is_canceled     = true;
        $this->save();

        ContractChargeCanceled::dispatch($this->fresh(['chargeType', 'counterparty', 'canceledBy']));
    }

    public function unCancel(): void
    {
        if (!$this->is_canceled) {
            return;
        }

        if ($this->hasLockedLiquidationVoucher()) {
            throw new DomainException('has_non_draft_voucher');
        }

        $this->canceled_at     = null;
        $this->canceled_reason = null;
        $this->canceled_by     = null;
        $this->is_canceled     = false;
        $this->save();
    }

    public function hasLockedLiquidationVoucher(): bool
    {
        return $this->voucherIsNonDraft($this->resolveVoucher('tenantLiquidationVoucher', 'tenant_liquidation_voucher_id'))
            || $this->voucherIsNonDraft($this->resolveVoucher('ownerLiquidationVoucher', 'owner_liquidation_voucher_id'));
    }

    /* =========================
     * Utilitarios
     * ========================= */
    public function isInPeriod(Carbon $period): bool
    {
        $start = $period->copy()->startOfMonth();
        $end   = $start->copy()->addMonth();
        return $this->effective_date !== null
            && $this->effective_date->greaterThanOrEqualTo($start)
            && $this->effective_date->lessThan($end);
    }

    private function getLoadedType(): ChargeType
    {
        return $this->relationLoaded('chargeType')
            ? $this->chargeType
            : $this->chargeType()->firstOrFail();
    }

    private function resolveVoucher(string $relation, string $foreignKey): ?Voucher
    {
        if (!$this->{$foreignKey}) {
            return null;
        }

        if ($this->relationLoaded($relation)) {
            return $this->{$relation};
        }

        return $this->{$relation}()->first();
    }

    private function voucherIsNonDraft(?Voucher $voucher): bool
    {
        if (!$voucher) return false;

        $status = $voucher->status;
        if ($status instanceof BackedEnum) {
            $status = $status->value;
        }
        if ($status === null) return false;

        return strtoupper((string) $status) !== 'DRAFT';
    }
}
