<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    use HasFactory;

    protected $table = 'role_users';

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
