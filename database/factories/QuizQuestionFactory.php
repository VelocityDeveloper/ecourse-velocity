<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'answer_mode' => QuizQuestion::MODE_SINGLE,
            'points' => fake()->numberBetween(1, 20),
            'position' => 1,
        ];
    }

    /**
     * Place the question at the given position in its quiz.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }
}
