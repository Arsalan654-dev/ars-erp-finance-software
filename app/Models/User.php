<?php

namespace App\Models;

use App\Models\Relations\HasPermissionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissionsTrait;

    /**
     * Per-user permission overrides (allow / deny).
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('type');
    }

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'department', 'designation',
        'employee_id', 'is_active', 'must_change_password', 'active_session_id',
        'last_activity_at', 'timezone', 'locale', 'two_factor_enabled',
        'two_factor_code', 'two_factor_expires_at', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'last_activity_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Effective permission check.
     *
     * Precedence (highest wins):
     *   1) Super admin  → everything
     *   2) Per-user override:
     *        - deny  → always false for that permission
     *        - allow → always true for that permission
     *   3) Role-based permissions
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $override = $this->userPermissions()
            ->whereHas('permission', fn ($q) => $q->where('slug', $permission))
            ->first();

        if ($override) {
            return $override->type === 'allow';
        }

        return $this->getAllRolePermissions()->contains('slug', $permission);
    }

    /**
     * All permissions granted via roles (no per-user overrides applied).
     */
    public function getAllRolePermissions()
    {
        return $this->roles->load('permissions')->pluck('permissions')->flatten()->unique('id');
    }

    /**
     * All effective permissions merged with per-user overrides.
     * Keyed by slug, value is one of: native (role), allow, deny.
     */
    public function getEffectivePermissionsAttribute()
    {
        $result = collect();

        foreach ($this->getAllRolePermissions() as $perm) {
            $result->put($perm->slug, 'role');
        }

        foreach ($this->permissions as $perm) {
            $result->put($perm->slug, $perm->pivot->type);
        }

        return $result;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Override the Notifiable trait's notifications() relation to use our
     * custom notifications table (user_id) instead of the default
     * notifiable_type / notifiable_id polymorphic columns.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }
}