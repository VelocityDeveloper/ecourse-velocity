<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
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
            'quiz_id' => Quiz::factory(),
            'started_at' => now(),
            'expires_at' => null,
            'max_score' => 0,
        ];
    }

    /**
     * Indicate that the attempt was handed in with the given score.
     */
    public function submitted(int $score = 0, int $maxScore = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'submitted_at' => now(),
            'answers' => [],
            'score' => $score,
            'max_score' => $maxScore,
        ]);
    }

    /**
     * Indicate that the attempt's timer ran out the given number of minutes ago.
     */
    public function expiredMinutesAgo(int $minutes): static
    {
        return $this->state(fn (array $attributes): array => [
            'started_at' => now()->subMinutes($minutes + 10),
            'expires_at' => now()->subMinutes($minutes),
        ]);
    }
}
