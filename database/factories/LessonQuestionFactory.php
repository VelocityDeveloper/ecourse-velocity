<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuestion>
 */
class LessonQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'user_id' => User::factory()->student(),
            'body' => rtrim(fake()->sentence(8), '.').'?',
        ];
    }
}
