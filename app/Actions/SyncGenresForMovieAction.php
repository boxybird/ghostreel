<?php

namespace App\Actions;

use App\Models\Genre;
use App\Models\Movie;

class SyncGenresForMovieAction
{
    /**
     * Sync genres for a movie via the pivot table.
     *
     * @param  array<int>  $tmdbGenreIds  Array of TMDB genre IDs
     */
    public function handle(Movie $movie, array $tmdbGenreIds): void
    {
        if ($tmdbGenreIds === []) {
            return;
        }

        $genreIds = Genre::whereIn('tmdb_id', $tmdbGenreIds)->pluck('id');
        $movie->genres()->sync($genreIds);
    }
}
