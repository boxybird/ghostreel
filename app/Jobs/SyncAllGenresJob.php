<?php

namespace App\Jobs;

use App\Models\Genre;
use App\Services\TmdbService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAllGenresJob implements ShouldQueue
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
        public int $pagesPerGenre = 5,
        public bool $syncMovies = true,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TmdbService $tmdb): void
    {
        Log::info('SyncAllGenresJob: Starting genre sync');

        // Fetch and store all genres
        $genres = $tmdb->getGenres();

        if ($genres->isEmpty()) {
            Log::warning('SyncAllGenresJob: No genres returned from TMDB');

            return;
        }

        foreach ($genres as $genreData) {
            Genre::updateOrCreate(
                ['tmdb_id' => $genreData['id']],
                ['name' => $genreData['name']]
            );
        }

        Log::info("SyncAllGenresJob: Synced {$genres->count()} genres to database");

        // Optionally dispatch jobs to sync movies for each genre
        if ($this->syncMovies) {
            foreach ($genres as $genreData) {
                SyncGenreMoviesJob::dispatch($genreData['id'], $this->pagesPerGenre)
                    ->delay(now()->addSeconds(rand(1, 10))); // Stagger the jobs
            }

            Log::info("SyncAllGenresJob: Dispatched {$genres->count()} genre movie sync jobs");
        }
    }
}
