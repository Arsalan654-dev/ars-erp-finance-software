<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    protected $fillable = ['code', 'name', 'type', 'currency', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}