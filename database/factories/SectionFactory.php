<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => Str::title(rtrim(fake()->sentence(3), '.')),
            'description' => fake()->sentence(),
            'position' => 1,
        ];
    }

    /**
     * Place the section at the given position in the curriculum.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }
}
