<?php

namespace App\Models;

use App\Enums\ChargeImpact;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $name
 * @property bool   $is_active
 * @property ChargeImpact $tenant_impact
 * @property ChargeImpact $owner_impact
 * @property bool   $requires_service_period
 * @property string|null $requires_counterparty // 'tenant'|'owner'|null
 * @property string $currency_policy            // CONTRACT_CURRENCY|CHARGE_CURRENCY|CONVERT_AT_TODAY
 */
class ChargeType extends Model
{
    // === Constantes de códigos (opcional pero práctico) ===
    public const CODE_RENT                = 'RENT';
    public const CODE_ADJ_DIFF_DEBIT      = 'ADJ_DIFF_DEBIT';
    public const CODE_ADJ_DIFF_CREDIT     = 'ADJ_DIFF_CREDIT';
    public const CODE_RECUP_TENANT_AGENCY = 'RECUP_TENANT_AGENCY';
    public const CODE_RECUP_OWNER_AGENCY  = 'RECUP_OWNER_AGENCY';
    public const CODE_RECUP_TENANT_OWNER  = 'RECUP_TENANT_OWNER';
    public const CODE_RECUP_OWNER_TENANT  = 'RECUP_OWNER_TENANT';
    public const CODE_BONIFICATION        = 'BONIFICATION';
    public const CODE_SELF_PAID_INFO      = 'SELF_PAID_INFO';

    // === Constantes de política de moneda ===
    public const CURR_CONTRACT = 'CONTRACT_CURRENCY';
    public const CURR_CHARGE   = 'CHARGE_CURRENCY';
    public const CURR_CONVERT  = 'CONVERT_AT_TODAY';

    protected $fillable = [
        'code','name','is_active',
        'tenant_impact','owner_impact',
        'requires_service_period','requires_counterparty',
        'currency_policy',
    ];

    protected $casts = [
        'is_active'               => 'bool',
        'requires_service_period' => 'bool',
        'tenant_impact'           => ChargeImpact::class,
        'owner_impact'            => ChargeImpact::class,
    ];

    // === Scopes útiles ===
    public function scopeActive($q)   { return $q->where('is_active', true); }
    public function scopeCode($q, $c) { return $q->where('code', $c); }

    // === Helpers de inclusión y signo ===
    /** @return array{include:bool, sign:int} */
    public function includeAndSignForTenant(): array
    {
        return $this->impactToIncludeAndSign($this->tenant_impact);
    }

    /** @return array{include:bool, sign:int} */
    public function includeAndSignForOwner(): array
    {
        return $this->impactToIncludeAndSign($this->owner_impact);
    }

    /** Devuelve +1, -1 o 0 según el impacto (y si se incluye en el total) */
    private function impactToIncludeAndSign(ChargeImpact $impact): array
    {
        return match ($impact) {
            ChargeImpact::ADD      => ['include' => true,  'sign' => +1],
            ChargeImpact::SUBTRACT => ['include' => true,  'sign' => -1],
            ChargeImpact::INFO,
            ChargeImpact::HIDDEN   => ['include' => false, 'sign' =>  0],
        };
    }

    // === Azúcar sintáctico ===
    public function includesInTenantDoc(): bool
    {
        return $this->tenant_impact === ChargeImpact::ADD
            || $this->tenant_impact === ChargeImpact::SUBTRACT;
    }

    public function includesInOwnerDoc(): bool
    {
        return $this->owner_impact === ChargeImpact::ADD
            || $this->owner_impact === ChargeImpact::SUBTRACT;
    }

    /** Calcula el monto firmado para inquilino según el impacto (amount positivo) */
    public function signedAmountForTenant(float $amount): float
    {
        $meta = $this->includeAndSignForTenant(); // ['include'=>bool, 'sign'=>-1|0|+1]
        $sign = $meta['sign'] ?? 0;
        return $sign * $amount;
    }

    public function signedAmountForOwner(float $amount): float
    {
        $meta = $this->includeAndSignForOwner(); // ['include'=>bool, 'sign'=>-1|0|+1]
        $sign = $meta['sign'] ?? 0;
        return $sign * $amount;
    }


    // === Validaciones de dominio rápidas (opcionales, defensivas) ===
    public function isCounterpartyRequired(): bool
    {
        return in_array($this->requires_counterparty, ['tenant','owner'], true);
    }

    public function requiresTenantCounterparty(): bool
    {
        return $this->requires_counterparty === 'tenant';
    }

    public function requiresOwnerCounterparty(): bool
    {
        return $this->requires_counterparty === 'owner';
    }
}
