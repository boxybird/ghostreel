<?php

namespace App\Actions;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Support\Collection;

class TransformMovieForDisplayAction
{
    /**
     * Transform a single movie model into a display array.
     *
     * @return array{id: int, db_id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, poster_url: ?string, click_count: int}
     */
    public function handle(Movie $movie, int $clickCount = 0): array
    {
        return [
            'id' => $movie->tmdb_id,
            'db_id' => $movie->id,
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'backdrop_path' => $movie->backdrop_path,
            'overview' => $movie->overview ?? '',
            'release_date' => $movie->release_date?->format('Y-m-d') ?? '',
            'vote_average' => (float) $movie->vote_average,
            'poster_url' => TmdbService::posterUrl($movie->poster_path),
            'click_count' => $clickCount,
        ];
    }

    /**
     * Transform a collection of movies into display arrays.
     *
     * @param  Collection<int, Movie>|array<int, Movie>  $movies
     * @param  array<int, int>  $clickCounts  Keyed by TMDB movie ID
     * @return Collection<int, array{id: int, db_id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, poster_url: ?string, click_count: int}>
     */
    public function collection(Collection|array $movies, array $clickCounts = []): Collection
    {
        return collect($movies)->map(
            fn (Movie $movie): array => $this->handle($movie, $clickCounts[$movie->tmdb_id] ?? 0)
        );
    }
}
