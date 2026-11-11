<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_number', 'business_name', 'legal_name', 'email', 'phone', 'country', 'address',
        'ein_encrypted', 'ein_hash', 'itin_encrypted', 'itin_hash',
        'utr_encrypted', 'utr_hash', 'ssn_encrypted', 'ssn_hash',
        'tax_type', 'status', 'notes', 'created_by', 'assigned_to',
    ];

    protected $appends = ['ein', 'itin', 'utr', 'ssn'];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Every client gets a CLI-XXXXX number unless one was supplied.
     */
    protected static function booted(): void
    {
        static::creating(function (Client $client): void {
            if (! $client->client_number) {
                $client->client_number = NumberSequenceService::nextClientNumber();
            }
        });
    }

    // Accessors / Mutators for encrypted fields
    public function getEinAttribute()
    {
        return $this->ein_encrypted ? decrypt($this->ein_encrypted) : null;
    }

    public function setEinAttribute($value)
    {
        $this->attributes['ein_encrypted'] = $value ? encrypt($value) : null;
        $this->attributes['ein_hash'] = $value ? hash('sha256', strtolower(trim($value))) : null;
    }

    public function getItinAttribute()
    {
        return $this->itin_encrypted ? decrypt($this->itin_encrypted) : null;
    }

    public function setItinAttribute($value)
    {
        $this->attributes['itin_encrypted'] = $value ? encrypt($value) : null;
        $this->attributes['itin_hash'] = $value ? hash('sha256', strtolower(trim($value))) : null;
    }

    public function getUtrAttribute()
    {
        return $this->utr_encrypted ? decrypt($this->utr_encrypted) : null;
    }

    public function setUtrAttribute($value)
    {
        $this->attributes['utr_encrypted'] = $value ? encrypt($value) : null;
        $this->attributes['utr_hash'] = $value ? hash('sha256', strtolower(trim($value))) : null;
    }

    public function getSsnAttribute()
    {
        return $this->ssn_encrypted ? decrypt($this->ssn_encrypted) : null;
    }

    public function setSsnAttribute($value)
    {
        $this->attributes['ssn_encrypted'] = $value ? encrypt($value) : null;
        $this->attributes['ssn_hash'] = $value ? hash('sha256', strtolower(trim($value))) : null;
    }

    // Masked display values
    public function getMaskedEinAttribute()
    {
        $val = $this->ein;

        return $val ? 'XX-XXX'.substr($val, -4) : null;
    }

    public function getMaskedSsnAttribute()
    {
        $val = $this->ssn;

        return $val ? 'XX-XXX'.substr($val, -4) : null;
    }

    public function getMaskedItinAttribute()
    {
        $val = $this->itin;

        return $val ? '9XX-XX'.substr($val, -4) : null;
    }

    public function getMaskedUtrAttribute()
    {
        $val = $this->utr;

        return $val ? str_repeat('X', strlen($val) - 4).substr($val, -4) : null;
    }

    // Relationships
    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function services()
    {
        return $this->hasMany(ClientService::class);
    }

    public function clientNotes()
    {
        return $this->hasMany(ClientNote::class);
    }

    public function history()
    {
        return $this->hasMany(ClientHistory::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function complianceRecords()
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
