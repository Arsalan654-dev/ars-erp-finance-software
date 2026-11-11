<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationStatusHistory extends Model
{
    protected $table = 'quotation_status_history';

    protected $fillable = ['quotation_id', 'from_status', 'to_status', 'changed_by', 'reason'];

    public $timestamps = false;

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}