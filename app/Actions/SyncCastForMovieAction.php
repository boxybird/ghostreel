<?php

namespace App\Actions;

use App\Models\Movie;
use App\Models\MovieCast;
use App\Models\Person;

class SyncCastForMovieAction
{
    /**
     * Sync cast members for a movie.
     *
     * @param  array<int, array{id: int, name: string, character: string, profile_path: ?string, order: int}>  $castData
     */
    public function handle(Movie $movie, array $castData): void
    {
        // Clear existing cast for this movie to avoid duplicates
        MovieCast::where('movie_id', $movie->id)->delete();

        foreach ($castData as $personData) {
            // Upsert person
            $person = Person::updateOrCreate(
                ['tmdb_id' => $personData['id']],
                [
                    'name' => $personData['name'],
                    'profile_path' => $personData['profile_path'],
                ]
            );

            // Create cast entry
            MovieCast::create([
                'movie_id' => $movie->id,
                'person_id' => $person->id,
                'character' => $personData['character'],
                'order' => $personData['order'],
            ]);
        }
    }
}
