<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopDocument extends Model
{
    protected $fillable = ['title', 'category', 'version', 'content', 'filename', 'department', 'status', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}