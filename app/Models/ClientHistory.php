<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientHistory extends Model
{
    protected $table = 'client_history';

    protected $fillable = ['client_id', 'user_id', 'action', 'metadata'];

    protected $casts = ['metadata' => 'json'];

    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}