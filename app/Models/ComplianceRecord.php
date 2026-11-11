<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'document_type', 'title', 'reference_number',
        'issuing_authority', 'issue_date', 'expiry_date', 'status',
        'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function renewalEvents()
    {
        return $this->hasMany(RenewalEvent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->expiry_date, false);
    }
}