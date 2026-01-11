<?php

namespace App\Actions;

use App\Models\MovieClick;

class GetMovieClickStatisticsAction
{
    /**
     * Get click statistics for a movie.
     *
     * @return array{today_count: int, total_count: int}
     */
    public function handle(int $tmdbMovieId): array
    {
        return [
            'today_count' => $this->getTodayCount($tmdbMovieId),
            'total_count' => $this->getTotalCount($tmdbMovieId),
        ];
    }

    /**
     * Get the click count for today (last 24 hours).
     */
    public function getTodayCount(int $tmdbMovieId): int
    {
        return MovieClick::query()
            ->where('tmdb_movie_id', $tmdbMovieId)
            ->where('clicked_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Get the total click count (all time).
     */
    public function getTotalCount(int $tmdbMovieId): int
    {
        return MovieClick::query()
            ->where('tmdb_movie_id', $tmdbMovieId)
            ->count();
    }
}
