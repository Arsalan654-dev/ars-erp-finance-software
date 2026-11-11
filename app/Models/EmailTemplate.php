<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'subject', 'body', 'variables', 'language', 'is_active'];

    protected $casts = ['variables' => 'json', 'is_active' => 'boolean'];
}