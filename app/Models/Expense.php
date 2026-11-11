<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_number', 'category', 'currency', 'amount', 'exchange_rate',
        'pkr_equivalent', 'expense_date', 'description', 'status',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}