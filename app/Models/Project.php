<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'project_number', 'client_id', 'invoice_id', 'title', 'description',
        'department', 'status', 'priority', 'start_date', 'due_date',
        'completed_at', 'assigned_by', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static array $ALLOWED_TRANSITIONS = [
        'pending' => ['in_progress', 'on_hold', 'cancelled'],
        'in_progress' => ['review', 'on_hold', 'completed'],
        'on_hold' => ['in_progress', 'cancelled'],
        'review' => ['in_progress', 'completed'],
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function members(): HasMany { return $this->hasMany(ProjectMember::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function statusHistory(): HasMany { return $this->hasMany(ProjectStatusHistory::class); }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::$ALLOWED_TRANSITIONS[$this->status] ?? []);
    }
}
