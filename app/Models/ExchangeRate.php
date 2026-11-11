<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = ['from_currency', 'to_currency', 'rate', 'date', 'source', 'set_by'];

    protected $casts = ['date' => 'date'];

    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}