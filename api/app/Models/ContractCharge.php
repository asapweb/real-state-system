<?php

namespace App\Models;

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

        // nuevas referencias a LIQ por lado
        'tenant_liquidation_voucher_id',
        'owner_liquidation_voucher_id',

        // marcas de asentado (cuando la LIQ pasa a NO-DRAFT)
        'tenant_settled_at',
        'owner_settled_at',

        'description',
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'effective_date'         => 'date',
        'due_date'               => 'date',
        'service_period_start'   => 'date',
        'service_period_end'     => 'date',
        'invoice_date'           => 'date',
        'tenant_settled_at'      => 'datetime',
        'owner_settled_at'       => 'datetime',
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

    // LIQ inquilino (documento de deuda / AR)
    public function tenantLiquidationVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'tenant_liquidation_voucher_id');
    }

    // LIQ propietario
    public function ownerLiquidationVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'owner_liquidation_voucher_id');
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

    /* =========================
     * Helpers de impacto y elegibilidad
     * ========================= */

    /** true si se debe incluir en la LIQ del inquilino para un período dado */
    public function shouldIncludeInTenantLiquidation(Carbon $period): bool
    {
        $type = $this->getLoadedType();
        $impact = $type->tenant_impact->value ?? $type->tenant_impact;

        if (!in_array($impact, ['add','subtract'], true)) return false;
        if (!$this->isInPeriod($period)) return false;

        // Si el tipo requiere contraparte tenant y no está seteada, no incluir
        if (($type->requires_counterparty ?? null) === 'tenant' && !$this->counterparty_contract_client_id) return false;

        // Si ya está asentado (LIQ posteada), no volver a incluir
        if (!is_null($this->tenant_settled_at)) return false;

        return true;
    }

    /** true si se debe incluir en la LIQ del propietario para un período dado */
    public function shouldIncludeInOwnerLiquidation(Carbon $period): bool
    {
        $type = $this->getLoadedType();
        $impact = $type->owner_impact->value ?? $type->owner_impact;

        if (!in_array($impact, ['add','subtract'], true)) return false;
        if (!$this->isInPeriod($period)) return false;

        if (($type->requires_counterparty ?? null) === 'owner' && !$this->counterparty_contract_client_id) return false;

        if (!is_null($this->owner_settled_at)) return false;

        return true;
    }

    /** Devuelve el monto firmado para inquilino (amount siempre positivo en DB) */
    public function signedAmountForTenant(): float
    {
        $type = $this->getLoadedType();
        return (float) $type->signedAmountForTenant((float) $this->amount);
    }

    /** Devuelve el monto firmado para propietario (amount siempre positivo en DB) */
    public function signedAmountForOwner(): float
    {
        $type = $this->getLoadedType();
        return (float) $type->signedAmountForOwner((float) $this->amount);
    }

    /** Marca “asentado” del lado tenant (cuando la LIQ pasa a NO-DRAFT) */
    public function markTenantSettled(?Carbon $at = null): void
    {
        $this->tenant_settled_at = $at ?: now();
        $this->save();
    }

    /** Marca “asentado” del lado owner */
    public function markOwnerSettled(?Carbon $at = null): void
    {
        $this->owner_settled_at = $at ?: now();
        $this->save();
    }

    /** Reabre (si el voucher vuelve a DRAFT o se anula) */
    public function reopenTenantSide(): void
    {
        $this->tenant_settled_at = null;
        $this->save();
    }

    public function reopenOwnerSide(): void
    {
        $this->owner_settled_at = null;
        $this->save();
    }

    /* =========================
     * Utilitarios
     * ========================= */

    /** período mensual (effective_date dentro del mes de $period) */
    public function isInPeriod(Carbon $period): bool
    {
        $start = $period->copy()->startOfMonth();
        $end   = $start->copy()->addMonth();
        return $this->effective_date !== null
            && $this->effective_date->greaterThanOrEqualTo($start)
            && $this->effective_date->lessThan($end);
    }

    /** Evita N+1 en helpers */
    private function getLoadedType(): ChargeType
    {
        return $this->relationLoaded('chargeType')
            ? $this->chargeType
            : $this->chargeType()->firstOrFail();
    }
}
