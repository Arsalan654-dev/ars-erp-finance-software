<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'version_number', 'filename', 'storage_path', 'size', 'checksum', 'change_notes', 'uploaded_by'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}