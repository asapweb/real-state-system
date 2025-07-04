<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booklet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'prefix', 'voucher_type_id', 'sale_point_id', 'default_currency', 'next_number'];

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function salePoint()
    {
        return $this->belongsTo(SalePoint::class);
    }
}
