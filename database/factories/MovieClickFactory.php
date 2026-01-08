<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MovieClick>
 */
class MovieClickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->ipv4(),
            'tmdb_movie_id' => fake()->numberBetween(1000, 999999),
            'movie_title' => fake()->sentence(3),
            'poster_path' => '/'.fake()->lexify('??????????').'.jpg',
            'clicked_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
