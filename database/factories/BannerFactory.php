<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'image_path' => Banner::IMAGE_DIRECTORY.'/'.fake()->uuid().'.jpg',
            'link_url' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * A banner hidden from the slider.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
