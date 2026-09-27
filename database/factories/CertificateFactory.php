<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
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
            'course_id' => Course::factory(),
            'code' => fn (): string => Certificate::newCode(),
            'student_name' => fake()->name(),
            'course_title' => fake()->sentence(3),
            'instructor_name' => fake()->name(),
            'final_percent' => 88,
            'letter' => 'A',
            'issued_at' => now(),
        ];
    }
}
