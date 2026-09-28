<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'user_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => null,
            'content' => '<p>'.fake()->paragraph(6).'</p><p>'.fake()->paragraph(6).'</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ];
    }

    /**
     * An article that is still being written.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }
}
