<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_number' => 'PRJ-' . strtoupper(uniqid()),
            'client_id' => fn () => \App\Models\Client::factory()->create()->id,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'department' => fake()->randomElement(['US TAX', 'UK TAX', 'LEGAL', 'GENERAL']),
            'status' => 'pending',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'start_date' => fake()->optional()->date(),
            'due_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'created_by' => fn () => \App\Models\User::factory()->create()->id,
            'assigned_by' => fn () => \App\Models\User::factory()->create()->id,
        ];
    }
}
