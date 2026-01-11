<?php

namespace App\Actions;

use App\Models\MovieClick;

class LogMovieClickAction
{
    /**
     * Log a movie click and return the click with updated count.
     *
     * @param  array{tmdb_movie_id: int, movie_title: string, poster_path: ?string}  $data
     * @return array{click: MovieClick, click_count: int}
     */
    public function handle(string $ipAddress, array $data): array
    {
        $click = MovieClick::create([
            'ip_address' => $ipAddress,
            'tmdb_movie_id' => $data['tmdb_movie_id'],
            'movie_title' => $data['movie_title'],
            'poster_path' => $data['poster_path'] ?? null,
            'clicked_at' => now(),
        ]);

        $clickCount = $this->getClickCountToday($data['tmdb_movie_id']);

        return [
            'click' => $click,
            'click_count' => $clickCount,
        ];
    }

    /**
     * Get the click count for a movie in the last 24 hours.
     */
    public function getClickCountToday(int $tmdbMovieId): int
    {
        return MovieClick::query()
            ->where('tmdb_movie_id', $tmdbMovieId)
            ->where('clicked_at', '>=', now()->subDay())
            ->count();
    }
}
