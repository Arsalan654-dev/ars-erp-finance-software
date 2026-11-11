<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankVerification extends Model
{
    protected $fillable = [
        'payment_id', 'bank_name', 'account_number', 'transaction_reference',
        'status', 'notes', 'verified_by', 'verified_at',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}