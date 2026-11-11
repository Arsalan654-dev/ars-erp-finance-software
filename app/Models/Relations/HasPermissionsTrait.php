<?php

namespace App\Models\Relations;

trait HasPermissionsTrait
{
    /**
     * Effective permission check with per-user overrides:
     *   1) Super admin → everything
     *   2) Per-user allow/deny override → highest precedence
     *   3) Role-based permissions
     */
    public function hasPermission(string $permission): bool
    {
        if (method_exists($this, 'isSuperAdmin') && $this->isSuperAdmin()) {
            return true;
        }

        // Per-user overrides first (deny beats role, allow grants directly)
        if (method_exists($this, 'userPermissions')) {
            $override = $this->userPermissions()
                ->whereHas('permission', fn ($q) => $q->where('slug', $permission))
                ->first();

            if ($override) {
                return $override->type === 'allow';
            }
        }

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $permission)) {
                return true;
            }
        }

        return false;
    }
}