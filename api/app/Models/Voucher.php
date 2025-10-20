<?php

namespace App\Models;

use App\Casts\VoucherStatusCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'booklet_id',
        'currency',
        'issue_date',
        'due_date',
        'client_id',
        'client_name',
        'client_address',
        'client_document_number',
        'client_document_type_name',
        'client_tax_condition_name',
        'client_tax_id_number',
        'contract_id',
        'period',
        'notes',
        'meta',
        'cae',
        'cae_expires_at',
        'afip_operation_type_id',
        'service_date_from',
        'service_date_to',
        'voucher_type_id',
        'voucher_type_short_name',
        'voucher_type_letter',
        'sale_point_number',
        'number',
        'status',
        'subtotal_exempt',
        'subtotal_untaxed',
        'subtotal_taxed',
        'subtotal_vat',
        'subtotal_other_taxes',
        'subtotal',
        'total',
        'generated_from_collection',
        'canceled_at',
        'canceled_by',
        'canceled_reason',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'service_date_from' => 'date',
        'service_date_to' => 'date',
        'afip_operation_type_id' => 'integer',
        'period' => 'date',
        'cae_expires_at' => 'date',
        'generated_from_collection' => 'boolean',
        'total' => 'decimal:2',
        'subtotal_exempt' => 'decimal:2',
        'subtotal_untaxed' => 'decimal:2',
        'subtotal_taxed' => 'decimal:2',
        'subtotal_vat' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'meta' => 'array',
        'status' => VoucherStatusCast::class,
        'canceled_at' => 'datetime',
    ];

    // --- Relaciones ---

    public function booklet()
    {
        return $this->belongsTo(Booklet::class);
    }

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(VoucherItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VoucherPayment::class);
    }
    public function associations()
    {
        return $this->hasMany(VoucherAssociation::class, 'voucher_id');
    }

    public function associatedBy()
    {
        return $this->hasMany(VoucherAssociation::class, 'associated_voucher_id');
    }

    // Helpers para acceso rápido
    public function associatedVouchers()
    {
        return $this->belongsToMany(
            Voucher::class,
            'voucher_associations',
            'voucher_id',
            'associated_voucher_id'
        );
    }

    public function adjusters()
    {
        return $this->belongsToMany(
            Voucher::class,
            'voucher_associations',
            'associated_voucher_id',
            'voucher_id'
        );
    }


    public function applications()
    {
        return $this->hasMany(VoucherApplication::class);
    }

    public function applicationsReceived()
    {
        return $this->hasMany(VoucherApplication::class, 'applied_to_id');
    }

    public function voucherAssociations()
    {
        return $this->hasMany(VoucherAssociation::class);
    }

    public function afipOperationType()
    {
        return $this->belongsTo(\App\Models\AfipOperationType::class, 'afip_operation_type_id');
    }

    // --- Accessors ---

    public function getFullNumberAttribute(): string
    {
        $prefix = str_pad($this->sale_point_number, 4, '0', STR_PAD_LEFT);
        $number = str_pad($this->number, 8, '0', STR_PAD_LEFT);

        return "{$this->voucher_type_short_name} {$this->voucher_type_letter} {$prefix}-{$number}";
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }
}
