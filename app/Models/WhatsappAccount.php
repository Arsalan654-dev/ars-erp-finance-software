<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAccount extends Model
{
    protected $fillable = [
        'name', 'provider', 'phone_number_id', 'api_url', 'api_token', 'verify_token', 'is_active',
    ];

    protected $hidden = [
        'api_token', 'verify_token',
    ];

    public function getApiTokenDecryptedAttribute(): ?string
    {
        return $this->api_token ? decrypt($this->api_token) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
