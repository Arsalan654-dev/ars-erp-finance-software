<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'source', 'campaign_name', 'business_name', 'contact_name', 'email', 'phone',
        'department', 'status', 'priority', 'notes', 'assigned_to', 'created_by',
        'converted_at', 'lost_reason', 'lost_note',
        'is_duplicate', 'duplicate_of_lead_id',
        'payment_type', 'payment_amount', 'payment_currency', 'payment_method', 'payment_reference',
        'payment_date', 'payment_screenshot_path', 'payment_approval_note', 'payment_approved_by',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
        'converted_at' => 'datetime',
        'is_duplicate' => 'boolean',
    ];

    protected static array $ALLOWED_TRANSITIONS = [
        'new' => ['contacted', 'qualified', 'lost'],
        'contacted' => ['follow_up', 'qualified', 'lost'],
        'follow_up' => ['qualified', 'lost'],
        'qualified' => ['quotation', 'lost'],
        'quotation' => ['converted', 'lost'],
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_lead_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_approved_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::$ALLOWED_TRANSITIONS[$this->status] ?? []);
    }
}
