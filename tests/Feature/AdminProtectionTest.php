<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class AdminProtectionTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = $this->createUserWithRole('super_admin');
    }

    public function test_cannot_deactivate_last_super_admin(): void
    {
        $this->actingAs($this->superAdmin);
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'is_active' => false,
        ])->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->fresh()->is_active);
    }

    public function test_cannot_remove_last_super_admin_role(): void
    {
        $financeRoleId = \App\Models\Role::where('name', 'finance')->first()->id;
        $this->actingAs($this->superAdmin);
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => [$financeRoleId],
        ])->assertSessionHas('error');

        $this->assertTrue($this->superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_can_deactivate_when_multiple_super_admins(): void
    {
        $otherAdmin = $this->createUserWithRole('super_admin');

        $this->actingAs($this->superAdmin);
        $this->patch("/erp/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'is_active' => false,
        ]);

        $this->assertFalse($this->superAdmin->fresh()->is_active);
    }
}
