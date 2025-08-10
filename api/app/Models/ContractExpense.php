<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ContractExpensePaidBy;
use App\Enums\ContractExpenseResponsibleParty;
use App\Enums\ContractExpenseStatus;

class ContractExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'service_type_id',
        'amount',
        'currency',
        'effective_date',
        'due_date',
        'paid_by',
        'responsible_party',
        'is_paid',
        'paid_at',
        'description',
        'included_in_voucher',
        'voucher_id',
        'status',
        'generated_credit_note_id',
        'liquidation_voucher_id',
        'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'is_paid' => 'boolean',
        'included_in_voucher' => 'boolean',
        'paid_by' => ContractExpensePaidBy::class,
        'responsible_party' => ContractExpenseResponsibleParty::class,
        'status' => ContractExpenseStatus::class,
        'settled_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function generatedCreditNote()
    {
        return $this->belongsTo(Voucher::class, 'generated_credit_note_id');
    }

    public function liquidationVoucher()
    {
        return $this->belongsTo(Voucher::class, 'liquidation_voucher_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getIsLockedAttribute(): bool
    {
        return in_array($this->status, [
            ContractExpenseStatus::BILLED,
            ContractExpenseStatus::CREDITED,
            ContractExpenseStatus::LIQUIDATED,
        ]);
    }



    /** Scope: gastos que afectan liquidación al propietario */
    public function scopeForOwner($query)
    {
        return $query->where('responsible_party', ContractExpenseResponsibleParty::OWNER);
    }

    /** Scope: gastos a cargo del inquilino */
    public function scopeForTenant($query)
    {
        return $query->where('responsible_party', ContractExpenseResponsibleParty::TENANT);
    }
}
