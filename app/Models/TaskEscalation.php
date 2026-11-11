<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskEscalation extends Model
{
    protected $fillable = ['task_id', 'escalation_level', 'reason', 'escalated_to', 'escalated_at', 'resolved_at'];

    protected $casts = [
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function escalatedTo()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }
}