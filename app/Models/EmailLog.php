<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email', 'recipient_name', 'subject', 'template_slug',
        'event_type', 'related_entity_id', 'related_entity_type',
        'status', 'error_message', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];
}