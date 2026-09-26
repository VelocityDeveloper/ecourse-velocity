<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
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
            'title' => Str::title(rtrim(fake()->sentence(4), '.')),
            'content_type' => fake()->randomElement(Lesson::CONTENT_TYPES),
            'content_url' => 'https://example.com/'.fake()->slug(),
            'duration_minutes' => fake()->numberBetween(3, 45),
            'position' => 1,
        ];
    }

    /**
     * Place the lesson at the given position in the section.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }
}
