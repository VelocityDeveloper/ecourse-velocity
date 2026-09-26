<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseReview>
 */
class CourseReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory()->published(),
            'user_id' => User::factory()->student(),
            'rating' => fake()->numberBetween(CourseReview::MIN_RATING, CourseReview::MAX_RATING),
            'comment' => fake()->sentence(14),
        ];
    }
}
