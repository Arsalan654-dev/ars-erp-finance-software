<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    protected $table = 'task_status_history';

    protected $fillable = ['task_id', 'from_status', 'to_status', 'changed_by', 'reason'];

    public $timestamps = false;

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}