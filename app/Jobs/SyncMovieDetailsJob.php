<?php

namespace App\Jobs;

use App\Actions\SeedMovieFromTmdbAction;
use App\Actions\SyncCastForMovieAction;
use App\Actions\SyncGenresForMovieAction;
use App\Models\Movie;
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
    public function handle(
        TmdbService $tmdb,
        SeedMovieFromTmdbAction $seedMovie,
        SyncGenresForMovieAction $syncGenres,
        SyncCastForMovieAction $syncCast,
    ): void {
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

        // Persist similar movies to the database and collect their IDs for syncing
        $similarMovieIds = [];
        foreach ($details['similar'] as $similarData) {
            $similarMovie = $seedMovie->handle($similarData, 'similar');
            $similarMovieIds[] = $similarMovie->id;
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
            'tmdb_popularity' => $popularity,
            'details_synced_at' => now(),
        ]);

        // Sync similar movies via pivot table
        $movie->similarMovies()->sync($similarMovieIds);

        // Sync genres via pivot table
        $syncGenres->handle($movie, $genreIds);

        // Sync cast members (defensive for incomplete API responses)
        /** @var array<int, array{id: int, name: string, character: string, profile_path: ?string, order: int}> $castData */
        $castData = $details['cast'] ?? []; // @phpstan-ignore-line
        $syncCast->handle($movie, $castData);

        Log::info("SyncMovieDetailsJob: Completed sync for movie '{$movie->title}'");
    }
}
