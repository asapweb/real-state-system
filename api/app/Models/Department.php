<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name', 'location',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
