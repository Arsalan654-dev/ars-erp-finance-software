<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-user permission override.
 *
 * type = 'allow' → permission explicitly granted to the user
 * type = 'deny'  → permission explicitly revoked from the user
 *
 * Allows a super admin to fine-tune a single user's access
 * beyond what their role(s) provide.
 */
class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = [
        'user_id',
        'permission_id',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}