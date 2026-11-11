<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = ['employee_id', 'date', 'clock_in', 'clock_out', 'status', 'notes'];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'string',
        'clock_out' => 'string',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}