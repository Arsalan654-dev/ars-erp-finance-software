<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(config('erp.lead_sources')),
            'business_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'department' => fake()->randomElement(config('erp.departments')),
            'status' => 'new',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'notes' => fake()->optional()->sentence(),
            'created_by' => fn () => User::factory()->create()->id,
        ];
    }
}
