<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payment_number', 'client_id', 'lead_id', 'currency', 'amount', 'screenshot_amount', 'amount_mismatch',
        'exchange_rate', 'pkr_equivalent', 'payment_method', 'reference_number', 'screenshot_path', 'status',
        'payment_date', 'notes', 'recorded_by', 'verified_by', 'verified_at',
        'acknowledged_by', 'acknowledged_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'amount_mismatch' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'payment_allocations')->withPivot('amount');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function bankVerification()
    {
        return $this->hasOne(BankVerification::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function acknowledger()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}