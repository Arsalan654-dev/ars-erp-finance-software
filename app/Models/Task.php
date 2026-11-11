<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_number', 'client_id', 'project_id', 'title', 'description', 'department',
        'priority', 'status', 'start_date', 'due_date', 'assignee_id', 'assigned_by',
        'completed_at', 'created_by', 'completed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignments')->withPivot('assigned_at');
    }

    public function currentAssignments()
    {
        return $this->hasMany(TaskAssignment::class)->whereNull('unassigned_at');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function escalations()
    {
        return $this->hasMany(TaskEscalation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }
}