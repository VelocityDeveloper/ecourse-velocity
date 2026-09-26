<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
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
            'course_id' => Course::factory()->published(),
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_by' => null,
            'enrolled_at' => now(),
        ];
    }

    /**
     * Indicate that the enrollment has been cancelled.
     */
    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Enrollment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Indicate that a staff member added the student to the course.
     */
    public function enrolledBy(User $enroller): static
    {
        return $this->state(fn (array $attributes): array => ['enrolled_by' => $enroller->id]);
    }
}
