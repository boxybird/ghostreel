<?php

namespace App\Jobs;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\MovieCast;
use App\Models\Person;
use App\Services\TmdbService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncMovieDetailsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $movieId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TmdbService $tmdb): void
    {
        $movie = Movie::find($this->movieId);

        if ($movie === null) {
            Log::warning("SyncMovieDetailsJob: Movie ID {$this->movieId} not found");

            return;
        }

        Log::info("SyncMovieDetailsJob: Syncing details for movie '{$movie->title}' (TMDB ID: {$movie->tmdb_id})");

        $details = $tmdb->getMovieDetails($movie->tmdb_id);

        if ($details === null) {
            Log::warning("SyncMovieDetailsJob: No details returned from TMDB for movie {$movie->tmdb_id}");

            return;
        }

        // Extract similar movie TMDB IDs
        $similarTmdbIds = collect($details['similar'])->pluck('id')->toArray();

        // Persist similar movies to the database (basic info only)
        foreach ($details['similar'] as $similarData) {
            $similarMovie = Movie::updateOrCreate(
                ['tmdb_id' => $similarData['id']],
                [
                    'title' => $similarData['title'],
                    'poster_path' => $similarData['poster_path'],
                    'backdrop_path' => $similarData['backdrop_path'],
                    'overview' => $similarData['overview'],
                    'release_date' => $similarData['release_date'] ?: null,
                    'vote_average' => $similarData['vote_average'],
                    'tmdb_popularity' => $similarData['popularity'],
                    'source' => 'search',
                ]
            );

            $this->syncGenres($similarMovie, $similarData['genre_ids']);
        }

        // Extract genre IDs from the genres array (defensive for incomplete API responses)
        /** @var array<int, array{id: int, name: string}> $genres */
        $genres = $details['genres'] ?? []; // @phpstan-ignore-line
        $genreIds = collect($genres)->pluck('id')->toArray();

        // Defensively extract values that may be missing in incomplete API responses
        /** @var array<int, array{name: string, job: string, department: string}> $crew */
        $crew = $details['crew'] ?? []; // @phpstan-ignore-line
        $popularity = $details['popularity'] ?? $movie->tmdb_popularity; // @phpstan-ignore-line

        // Update movie with extended details
        $movie->update([
            'tagline' => $details['tagline'] ?? null,
            'runtime' => $details['runtime'] ?? null,
            'crew' => $crew,
            'similar_tmdb_ids' => $similarTmdbIds,
            'tmdb_popularity' => $popularity,
            'details_synced_at' => now(),
        ]);

        // Sync genres via pivot table
        $this->syncGenres($movie, $genreIds);

        // Sync cast members (defensive for incomplete API responses)
        /** @var array<int, array{id: int, name: string, character: string, profile_path: ?string, order: int}> $castData */
        $castData = $details['cast'] ?? []; // @phpstan-ignore-line
        $this->syncCast($movie, $castData);

        Log::info("SyncMovieDetailsJob: Completed sync for movie '{$movie->title}'");
    }

    /**
     * Sync cast members for the movie.
     *
     * @param  array<int, array{id: int, name: string, character: string, profile_path: ?string, order: int}>  $castData
     */
    private function syncCast(Movie $movie, array $castData): void
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

    /**
     * Sync genres for the movie via the pivot table.
     *
     * @param  array<int, int>  $tmdbGenreIds
     */
    private function syncGenres(Movie $movie, array $tmdbGenreIds): void
    {
        if ($tmdbGenreIds === []) {
            return;
        }

        $genreIds = Genre::whereIn('tmdb_id', $tmdbGenreIds)->pluck('id');
        $movie->genres()->sync($genreIds);
    }
}
