<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    protected $fillable = ['client_id', 'first_name', 'last_name', 'email', 'phone', 'position', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}