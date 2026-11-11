<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceApproval extends Model
{
    protected $fillable = ['invoice_id', 'status', 'approved_by', 'rejected_by', 'notes', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejectedBy() { return $this->belongsTo(User::class, 'rejected_by'); }
}
