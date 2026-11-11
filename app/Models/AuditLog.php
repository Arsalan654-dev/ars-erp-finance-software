<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'user_agent', 'metadata'];

    protected $casts = ['metadata' => 'json', 'created_at' => 'datetime'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
