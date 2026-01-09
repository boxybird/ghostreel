<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Genre>
 */
class GenreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1, 100000),
            'name' => fake()->randomElement([
                'Action', 'Adventure', 'Animation', 'Comedy', 'Crime',
                'Documentary', 'Drama', 'Family', 'Fantasy', 'History',
                'Horror', 'Music', 'Mystery', 'Romance', 'Science Fiction',
                'TV Movie', 'Thriller', 'War', 'Western',
            ]),
        ];
    }
}
