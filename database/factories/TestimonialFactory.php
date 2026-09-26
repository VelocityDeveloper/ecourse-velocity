<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'subtitle' => fake()->company(),
            'quote' => fake()->sentence(12),
            'rating' => 5,
            'photo_path' => null,
            'mask_name' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * A testimonial hidden from the homepage.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
