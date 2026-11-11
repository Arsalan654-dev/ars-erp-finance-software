<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailQueue extends Model
{
    protected $table = 'email_queue';
    protected $fillable = [
        'to_email', 'to_name', 'subject', 'body', 'event_type',
        'attachments', 'status', 'retry_count', 'max_retries',
        'scheduled_at', 'processed_at', 'error',
    ];

    protected $casts = [
        'attachments' => 'json',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}