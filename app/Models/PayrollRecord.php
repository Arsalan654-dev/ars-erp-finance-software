<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $fillable = [
        'employee_id', 'period', 'currency', 'gross_amount', 'deductions',
        'net_amount', 'exchange_rate', 'pkr_equivalent', 'status',
        'payment_date', 'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = ['payment_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}