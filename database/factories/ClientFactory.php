<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'legal_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'address' => fake()->address(),
            'tax_type' => fake()->randomElement(['US', 'UK', 'CA']),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
