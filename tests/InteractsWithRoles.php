<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;

trait InteractsWithRoles
{
    protected function seedPermissions(): void
    {
        (new RolePermissionSeeder)->run();
    }

    protected function assignRole(User $user, string $roleName): void
    {
        $this->seedPermissions();
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $role = Role::create(['name' => $roleName]);
        }
        DB::table('role_users')->insertOrIgnore([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->load('roles');
    }

    protected function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $this->assignRole($user, $roleName);
        return $user;
    }
}
