<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'compliance_record_id', 'filename', 'original_filename',
        'mime_type', 'size', 'storage_path', 'storage_disk', 'category',
        'description', 'checksum', 'uploaded_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function complianceRecord()
    {
        return $this->belongsTo(ComplianceRecord::class);
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function attachable()
    {
        return $this->morphTo();
    }
}