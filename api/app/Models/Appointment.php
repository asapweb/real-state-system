<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'start_at',
        'booked_by',
        'received_at',
        'received_by',
        'attended_start_at',
        'attended_start_by',
        'attended_end_at',
        'attended_end_by',
        'employee_id',
        'department_id',
        'notes',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }
    public function attendantStart()
    {
        return $this->belongsTo(User::class, 'attended_start_by', 'id');
    }
    public function attendantEnd()
    {
        return $this->belongsTo(User::class, 'attended_end_by', 'id');
    }
}
