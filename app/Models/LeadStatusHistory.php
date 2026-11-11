<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatusHistory extends Model
{
    protected $table = 'lead_status_history';
    protected $fillable = ['lead_id', 'from_status', 'to_status', 'changed_by', 'reason'];
    public $timestamps = false;

    public function lead() { return $this->belongsTo(Lead::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
