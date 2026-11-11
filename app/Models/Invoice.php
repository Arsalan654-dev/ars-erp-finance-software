<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'client_id', 'quotation_id', 'duplicate_of_id', 'currency', 'exchange_rate',
        'subtotal', 'discount', 'tax', 'total', 'paid_amount', 'balance',
        'status', 'payment_status', 'issue_date', 'due_date',
        'locked_at', 'locked_by', 'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(InvoiceStatusHistory::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'payment_allocations')->withPivot('amount');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['locked', 'partially_paid', 'paid']);
    }

    public function approvals()
    {
        return $this->hasMany(InvoiceApproval::class);
    }

    public function latestApproval()
    {
        return $this->hasOne(InvoiceApproval::class)->latest();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
