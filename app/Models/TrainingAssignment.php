<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'training_course_id', 'employee_id', 'assigned_date',
        'completed_date', 'status', 'score',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'completed_date' => 'date',
    ];

    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}