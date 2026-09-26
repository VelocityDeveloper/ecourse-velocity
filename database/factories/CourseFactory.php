<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => Str::title(rtrim($title, '.')),
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'instructor_id' => User::factory()->instructor(),
            'price' => fake()->randomFloat(2, 0, 500),
            'level' => fake()->randomElement(Course::LEVELS),
            'thumbnail_path' => null,
            'status' => Course::STATUS_DRAFT,
        ];
    }

    /**
     * Indicate that the course is waiting for admin approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => Course::STATUS_PENDING]);
    }

    /**
     * Indicate that the course has been approved and is live.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => Course::STATUS_PUBLISHED]);
    }

    /**
     * Indicate that the course has been taken offline.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => Course::STATUS_ARCHIVED]);
    }

    /**
     * Indicate which instructor owns the course.
     */
    public function ownedBy(User $instructor): static
    {
        return $this->state(fn (array $attributes): array => ['instructor_id' => $instructor->id]);
    }
}
