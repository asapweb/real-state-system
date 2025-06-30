<?php

namespace App\Models;

use App\Enums\CollectionItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Collection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'status',
        'currency',
        'issue_date',
        'due_date',
        'period',
        'total_amount',
        'notes',
        'paid_at',
        'paid_by_user_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

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
        return $this->hasMany(CollectionItem::class);
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function cancel(): void
    {
        if ($this->status === 'canceled') {
            return;
        }

        DB::transaction(function () {
            $this->status = 'canceled';
            $this->paid_at = null;
            $this->paid_by_user_id = null;
            $this->save();

            $this->items()
                ->where('type', CollectionItemType::Service)
                ->get()
                ->each(function ($item) {
                    $expenseId = $item->meta['expense_id'] ?? null;
                    if ($expenseId) {
                        $expense = \App\Models\ContractExpense::find($expenseId);
                        if ($expense) {
                            $expense->included_in_collection = false;
                            $expense->save();
                        }
                    }
                });
        });
    }
}
