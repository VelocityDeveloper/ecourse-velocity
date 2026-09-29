<?php

namespace Database\Factories;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorApplication>
 */
class InstructorApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'headline' => 'Web Developer di '.fake()->company(),
            'expertise' => 'Pemrograman Web',
            'experience' => fake()->paragraph(),
            'motivation' => fake()->paragraph(),
            'portfolio_url' => fake()->url(),
            'phone' => '0812'.fake()->numerify('########'),
            'status' => InstructorApplication::STATUS_PENDING,
        ];
    }
}
