<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $manager;

    private User $associate;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createUserWithRole('csr_manager');
        $this->associate = $this->createUserWithRole('team_associate');
        $admin = $this->createUserWithRole('super_admin');
        $this->client = Client::create(['business_name' => 'Task Client', 'status' => 'active', 'created_by' => $admin->id]);
    }

    private function createTask(string $status = 'pending'): Task
    {
        return Task::create([
            'title' => 'Tax Return', 'task_number' => 'TSK-'.uniqid(),
            'client_id' => $this->client->id, 'status' => $status,
            'priority' => 'high', 'created_by' => $this->manager->id,
        ]);
    }

    public function test_pending_can_be_assigned(): void
    {
        $task = $this->createTask('pending');
        $this->actingAs($this->manager);
        $this->post("/erp/tasks/{$task->id}/assign", ['assignee_id' => $this->associate->id])->assertRedirect();
        $this->assertEquals('assigned', $task->fresh()->status);
    }

    public function test_assigned_can_be_started(): void
    {
        $task = $this->createTask('assigned');
        $task->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertRedirect();
        $this->assertEquals('in_progress', $task->fresh()->status);
    }

    public function test_in_progress_can_be_reviewed(): void
    {
        $task = $this->createTask('in_progress');
        $task->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/status", ['status' => 'review'])->assertRedirect();
        $this->assertEquals('review', $task->fresh()->status);
    }

    public function test_review_can_be_completed(): void
    {
        $task = $this->createTask('review');
        $task->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->manager);
        $this->post("/erp/tasks/{$task->id}/status", ['status' => 'completed'])->assertRedirect();
        $this->assertEquals('completed', $task->fresh()->status);
    }

    public function test_completed_cannot_go_back_to_pending(): void
    {
        $task = $this->createTask('completed');
        $task->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/status", ['status' => 'pending'])->assertSessionHas('error');
    }

    public function test_cancelled_cannot_be_restarted(): void
    {
        $task = $this->createTask('cancelled');
        $task->assignments()->create(['user_id' => $this->associate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertSessionHas('error');
    }

    public function test_associate_can_claim_pool_task(): void
    {
        $task = $this->createTask('pending');
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/claim")->assertRedirect();
        $this->assertEquals('assigned', $task->fresh()->status);
        $this->assertDatabaseHas('task_assignments', ['task_id' => $task->id, 'user_id' => $this->associate->id]);
    }

    public function test_task_status_change_creates_history(): void
    {
        $task = $this->createTask('pending');
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/claim");
        $this->assertDatabaseHas('task_status_history', ['task_id' => $task->id, 'to_status' => 'assigned']);
    }

    public function test_associate_cannot_assign_tasks(): void
    {
        $task = $this->createTask('pending');
        $this->actingAs($this->associate);
        $this->post("/erp/tasks/{$task->id}/assign", ['assignee_id' => $this->associate->id])->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_associate_cannot_see_other_users_tasks(): void
    {
        $otherAssociate = $this->createUserWithRole('team_associate');
        $task = $this->createTask('assigned');
        $task->assignments()->create(['user_id' => $otherAssociate->id, 'assigned_by' => $this->manager->id]);
        $this->actingAs($this->associate);
        $this->get("/erp/tasks/{$task->id}")->assertStatus(302)->assertSessionHas('permission_denied');
    }
}
