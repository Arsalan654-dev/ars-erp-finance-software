<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceStatusHistory extends Model
{
    protected $table = 'invoice_status_history';

    protected $fillable = ['invoice_id', 'from_status', 'to_status', 'changed_by', 'reason'];

    public $timestamps = false;

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}