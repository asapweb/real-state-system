<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ContractStatus;
use App\Enums\CommissionType;
use App\Enums\CommissionPayer;
use App\Enums\ContractClientRole;
use App\Enums\DepositType;
use App\Enums\DepositHolder;
use App\Enums\PenaltyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_application_id',
        'start_date',
        'end_date',
        'monthly_amount',
        'currency',
        'payment_day',
        'prorate_first_month',
        'prorate_last_month',
        'commission_type',
        'commission_amount',
        'commission_payer',
        'is_one_time',
        'insurance_required',
        'insurance_amount',
        'insurance_company_name',
        'owner_share_percentage',
        'deposit_amount',
        'deposit_currency',
        'deposit_type',
        'deposit_holder',
        'has_penalty',
        'penalty_type',
        'penalty_value',
        'penalty_grace_days',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'prorate_first_month' => 'boolean',
        'prorate_last_month' => 'boolean',
        'is_one_time' => 'boolean',
        'insurance_required' => 'boolean',
        'insurance_amount' => 'decimal:2',
        'owner_share_percentage' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'has_penalty' => 'boolean',
        'penalty_value' => 'decimal:2',
        'penalty_grace_days' => 'integer',
        'status' => ContractStatus::class,
        'commission_type' => CommissionType::class,
        'commission_payer' => CommissionPayer::class,
        'deposit_type' => DepositType::class,
        'deposit_holder' => DepositHolder::class,
        'penalty_type' => PenaltyType::class,
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalApplication()
    {
        return $this->belongsTo(RentalApplication::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function adjustments()
    {
        return $this->hasMany(ContractAdjustment::class);
    }

    public function clients()
    {
        return $this->hasMany(ContractClient::class);
    }

    public function services()
    {
        return $this->hasMany(ContractService::class);
    }

    public function collections()
    {
        return $this->hasMany(Voucher::class)
            ->where('voucher_type_id', \App\Enums\VoucherType::COB);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }


    public function expenses()
    {
        return $this->hasMany(ContractExpense::class);
    }

    // --- Métodos auxiliares ---

    public static function scopeActiveDuring(Builder $query, Carbon $period): Builder
    {
        $start = $period->copy()->startOfMonth()->toDateString();
        $end = $period->copy()->endOfMonth()->toDateString();

        \Log::debug("cobranza", [
            "start" => $start,
            "end" => $end,
        ]);
        return $query->whereDate('start_date', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $start);
            });
    }


    public function mainTenant()
    {
        $tenant = $this->clients()->where('role', ContractClientRole::TENANT)->first();
        return $tenant;
    }


    /**
     * Calcula el punitorio por mora correspondiente al período dado,
     * considerando la fecha real de pago y el vencimiento con días de gracia.
     *
     * Si el pago se realiza en o antes del último día permitido (vencimiento + gracia), no se aplica punitorio.
     */
    public function calculateLateFee(Carbon $period, Carbon $paymentDate): ?array
    {
        if (!$this->has_penalty || !$this->payment_day || !$this->penalty_value) {
            return null;
        }

        // Calcular fecha de vencimiento
        $dueDate = $period->copy()->day($this->payment_day)->startOfDay();

        // Calcular fecha límite con días de gracia
        $grace = $this->penalty_grace_days ?? 0;
        $limit = $dueDate->copy()->addDays($grace);

        // Si el pago es hasta el límite inclusive, no hay punitorio
        if ($paymentDate->startOfDay()->lte($limit)) {
            return null;
        }

        $daysLate = $limit->diffInDays($paymentDate->startOfDay());
        $base = $this->calculateRentForPeriod($period)['amount'];

        $amount = match ($this->penalty_type->value ?? $this->penalty_type) {
            'fixed' => $this->penalty_value,
            'percentage' => round($base * ($this->penalty_value / 100), 2),
            default => 0,
        };

        return [
            'due_date' => $dueDate->toDateString(),
            'grace_days' => $grace,
            'payment_date' => $paymentDate->toDateString(),
            'days_late' => $daysLate,
            'penalty_type' => $this->penalty_type,
            'penalty_value' => $this->penalty_value,
            'amount' => $amount,
        ];
    }


    public function shouldChargeCommission(Carbon $period): bool
    {
        if ($this->commission_type === 'none' || $this->commission_payer !== CommissionPayer::TENANT) {
            return false;
        }

        if ($this->is_one_time && $period->format('Y-m') !== $this->start_date->format('Y-m')) {
            return false;
        }

        return true;
    }

    public function activeServicesForRecovery()
    {
        return $this->services()
            ->where('is_active', true)
            ->where('paid_by', 'agency')
            ->get();
    }

    public function hasCollectionForPeriodAndCurrency(string $period, string $currency): bool
    {
        return $this->collections
            ->where('status', '!=', 'canceled')
            ->first(fn($c) => $c->period === $period && $c->currency === $currency) !== null;
    }

    public function calculateRentForPeriod(Carbon $period): array
    {
        $adjustmentResult = $this->applyAdjustmentsWithDetail($this->monthly_amount, $period);
        $adjustedAmount = $adjustmentResult['amount'];
        $appliedAdjustment = $adjustmentResult['adjustment'];


        $monthStart = $period->copy()->startOfMonth();
        $monthEnd = $period->copy()->endOfMonth();

        $from = $this->start_date->greaterThan($monthStart) ? $this->start_date->copy() : $monthStart;
        $to = $this->end_date->lessThan($monthEnd) ? $this->end_date->copy() : $monthEnd;

        $daysInMonth = $period->daysInMonth;
        $proratedDays = $from->startOfDay()->diffInDays($to->startOfDay()) + 1;

        $shouldProrate = false;

        if ($this->prorate_first_month && $period->isSameMonth($this->start_date)) {
            $shouldProrate = true;
        }

        if ($this->prorate_last_month && $period->isSameMonth($this->end_date)) {
            $shouldProrate = true;
        }

        if ($shouldProrate && $proratedDays < $daysInMonth) {
            $factor = $proratedDays / $daysInMonth;
            $final = round($adjustedAmount * $factor, 2);

            return [
                'amount' => $final,
                'meta' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'prorated_days' => $proratedDays,
                    'month_days' => $daysInMonth,
                    'prorate_percentage' => round($factor, 4),
                    'original_amount' => $adjustedAmount,
                    'adjustment_id' => $appliedAdjustment?->id,
                    'adjustment_type' => $appliedAdjustment?->type,
                    'adjustment_value' => $appliedAdjustment?->value,
                    'adjustment' => $appliedAdjustment,
                ],        ];

        }

        return [
            'amount' => $adjustedAmount,
            'meta' => [
                'adjustment_id' => $appliedAdjustment?->id,
                'adjustment_type' => $appliedAdjustment?->type,
                'adjustment_value' => $appliedAdjustment?->value,
                'adjustment' => $appliedAdjustment,
            ],
        ];

    }



    public function applyAdjustments(float $base, Carbon $period): float
    {
        $adjustment = $this->adjustments()
            ->where('effective_date', '<=', $period->endOfMonth())
            ->orderByDesc('effective_date')
            ->first();

        if (!$adjustment) return $base;

        return match ($adjustment->type->value ?? $adjustment->type) {
            'fixed' => $adjustment->value,
            'percentage' => round($base * (1 + $adjustment->value / 100), 2),
            'index' => round($base * (1 + $adjustment->value / 100), 2),
            'negotiated' => $adjustment->value,
            default => $base,
        };
    }


    public function calculatePenaltyForPeriod(Carbon $period): ?array
    {
        $previous = Collection::where('contract_id', $this->id)
            ->where('period', $period->copy()->subMonth()->format('Y-m'))
            ->where('status', 'pending')
            ->first();

        if (!$previous || !$this->has_penalty) {
            return null;
        }

        $grace = $this->penalty_grace_days ?? 0;
        $limit = Carbon::parse($previous->due_date)->addDays($grace);

        if (now()->lt($limit)) return null;

        $base = $previous->total_amount;
        $value = match ($this->penalty_type->value ?? $this->penalty_type) {
            'fixed' => $this->penalty_value,
            'percentage' => round($base * ($this->penalty_value / 100), 2),
            default => 0,
        };

        return [
            'related_period' => $previous->period,
            'original_due_date' => $previous->due_date,
            'applied_on' => now()->toDateString(),
            'penalty_type' => $this->penalty_type,
            'penalty_value' => $this->penalty_value,
            'amount' => $value,
            'grace_days' => $grace,
        ];
    }

    public function applyAdjustmentsWithDetail(float $base, Carbon $period): array
    {
        $adjustment = $this->adjustments()
            ->where('effective_date', '<=', $period->endOfMonth())
            ->orderByDesc('effective_date')
            ->first();

        if (!$adjustment || $adjustment->value === null) {
            return [
                'amount' => $base,
                'adjustment' => null,
            ];
        }

        $amount = match ($adjustment->type->value ?? $adjustment->type) {
            'fixed' => $adjustment->value,
            'percentage', 'index' => round($base * (1 + $adjustment->value / 100), 2),
            'negotiated' => $adjustment->value,
            default => $base,
        };

        return [
            'amount' => $amount,
            'adjustment' => $adjustment,
        ];
    }


}
