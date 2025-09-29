<?php

namespace App\Models;

use App\Enums\VoucherItemType;
use App\Enums\VoucherItemImpact;   // enum del ítem: add|subtract
use App\Enums\ChargeImpact;        // enum del tipo de cargo: add|subtract|info|hidden
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use InvalidArgumentException;

class VoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'contract_charge_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'vat_amount',
        'tax_rate_id',
        'subtotal_with_vat',
        'impact', // add|subtract (VoucherItemImpact)
        'meta',
    ];

    protected $casts = [
        'type'               => VoucherItemType::class,
        'impact'             => VoucherItemImpact::class, // casteo al enum del ítem
        'quantity'           => 'integer',
        'unit_price'         => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'vat_amount'         => 'decimal:2',
        'subtotal_with_vat'  => 'decimal:2',
        'meta'               => 'array',
    ];

    // Default por si nunca se setea explícito
    protected $attributes = [
        'impact' => 'add',
    ];

    /* =========================
     * Relaciones
     * ========================= */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function contractCharge()
    {
        return $this->belongsTo(ContractCharge::class);
    }

    /* =========================
     * Normalización de impact
     * ========================= */
    /**
     * Acepta:
     * - VoucherItemImpact (enum del ítem) -> add|subtract
     * - ChargeImpact (enum del ChargeType) -> add|subtract|info|hidden (info/hidden rechazados)
     * - string ("add" | "subtract")
     */
    public function setImpactAttribute($value): void
    {
        // 1) Enum correcto del ítem
        if ($value instanceof VoucherItemImpact) {
            $this->attributes['impact'] = $value->value;
            return;
        }

        // 2) Enum del tipo de cargo (con info/hidden invalidados)
        if ($value instanceof ChargeImpact) {
            if (!$value->isIncluded()) {
                throw new InvalidArgumentException('VoucherItem.impact must be add or subtract (not info/hidden).');
            }
            $this->attributes['impact'] = $value->value; // 'add' | 'subtract'
            return;
        }

        // 3) String (o escalar) -> normalizar
        $v = strtolower(trim((string) $value));
        if ($v === '' || $v === 'null') {
            $v = VoucherItemImpact::Add->value; // default seguro
        }

        if (!in_array($v, [VoucherItemImpact::Add->value, VoucherItemImpact::Subtract->value], true)) {
            throw new InvalidArgumentException('Impact must be add or subtract');
        }

        $this->attributes['impact'] = $v;
    }
}
