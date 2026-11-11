<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'ledger_account_id', 'entry_type', 'amount', 'currency',
        'invoice_id', 'payment_id', 'expense_id', 'description',
        'entry_date', 'created_by',
    ];

    protected $casts = ['entry_date' => 'date'];

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}