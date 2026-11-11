<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = ['user_id', 'event_type', 'ip_address', 'user_agent', 'metadata'];

    protected $casts = ['metadata' => 'json'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}