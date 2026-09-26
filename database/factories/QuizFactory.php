<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => Str::title(rtrim(fake()->sentence(3), '.')),
            'description' => fake()->sentence(),
            'position' => 1,
        ];
    }

    /**
     * Place the quiz at the given position in its section.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }
}
