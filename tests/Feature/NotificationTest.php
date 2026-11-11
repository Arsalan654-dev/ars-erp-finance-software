<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $user1;

    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user1 = $this->createUserWithRole('super_admin');
        $this->user2 = $this->createUserWithRole('finance');
    }

    public function test_cannot_mark_other_users_notification(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user1->id, 'type' => 'task_assigned',
            'title' => 'Task Assigned', 'body' => 'Test notification',
            'is_read' => false,
        ]);
        $this->actingAs($this->user2);
        $this->post("/erp/notifications/{$notification->id}/read")->assertStatus(302)->assertSessionHas('permission_denied');
        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_can_mark_own_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user1->id, 'type' => 'payment_received',
            'title' => 'Payment Received', 'body' => 'Test notification',
            'is_read' => false,
        ]);
        $this->actingAs($this->user1);
        $this->post("/erp/notifications/{$notification->id}/read")->assertRedirect();
        $this->assertTrue($notification->fresh()->is_read);
    }
}
