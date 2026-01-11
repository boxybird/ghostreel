<?php

namespace App\Actions;

use App\Models\Genre;
use App\Models\Movie;

class SeedMovieFromTmdbAction
{
    /**
     * Valid source values for the movies table enum.
     */
    private const VALID_SOURCES = ['trending', 'search'];

    /**
     * Create or update a movie from TMDB API data.
     *
     * @param  array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: ?string, release_date: ?string, vote_average: float, popularity: float, genre_ids?: array<int>}  $tmdbData
     * @param  string  $source  The source type - will be mapped to 'trending' or 'search'
     */
    public function handle(array $tmdbData, string $source = 'search'): Movie
    {
        // Map source to valid enum value (only 'trending' stays as-is, everything else becomes 'search')
        $validSource = in_array($source, self::VALID_SOURCES, true) ? $source : 'search';

        $movie = Movie::updateOrCreate(
            ['tmdb_id' => $tmdbData['id']],
            [
                'title' => $tmdbData['title'],
                'poster_path' => $tmdbData['poster_path'],
                'backdrop_path' => $tmdbData['backdrop_path'],
                'overview' => $tmdbData['overview'],
                'release_date' => $tmdbData['release_date'] ?: null,
                'vote_average' => $tmdbData['vote_average'],
                'tmdb_popularity' => $tmdbData['popularity'],
                'source' => $validSource,
            ]
        );

        // Sync genres if provided
        if (! empty($tmdbData['genre_ids'])) {
            $this->syncGenres($movie, $tmdbData['genre_ids']);
        }

        return $movie;
    }

    /**
     * Sync genres for the movie via the pivot table.
     *
     * @param  array<int>  $tmdbGenreIds
     */
    private function syncGenres(Movie $movie, array $tmdbGenreIds): void
    {
        $genreIds = Genre::whereIn('tmdb_id', $tmdbGenreIds)->pluck('id');
        $movie->genres()->sync($genreIds);
    }
}
