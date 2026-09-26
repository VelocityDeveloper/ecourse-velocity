<?php

namespace Database\Factories;

use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonReply>
 */
class LessonReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_question_id' => LessonQuestion::factory(),
            'user_id' => User::factory()->instructor(),
            'body' => fake()->sentence(12),
        ];
    }
}
