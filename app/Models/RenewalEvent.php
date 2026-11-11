<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalEvent extends Model
{
    protected $fillable = ['compliance_record_id', 'event_type', 'triggered_at', 'metadata'];

    protected $casts = [
        'triggered_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function complianceRecord()
    {
        return $this->belongsTo(ComplianceRecord::class);
    }
}