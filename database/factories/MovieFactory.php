<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movie>
 */
class MovieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1000, 999999),
            'title' => fake()->sentence(3),
            'poster_path' => '/'.fake()->lexify('??????????').'.jpg',
            'backdrop_path' => '/'.fake()->lexify('??????????').'.jpg',
            'overview' => fake()->paragraph(),
            'release_date' => fake()->dateTimeBetween('-30 years', 'now'),
            'vote_average' => fake()->randomFloat(1, 0, 10),
            'source' => fake()->randomElement(['trending', 'search']),
        ];
    }

    /**
     * Indicate the movie is from trending.
     */
    public function trending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => 'trending',
        ]);
    }

    /**
     * Indicate the movie is from search.
     */
    public function fromSearch(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => 'search',
        ]);
    }
}
