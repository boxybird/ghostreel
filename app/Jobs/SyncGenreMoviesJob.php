<?php

namespace App\Jobs;

use App\Models\GenreSnapshot;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncGenreMoviesJob implements ShouldQueue
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
        public int $genreId,
        public int $pages = 5,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TmdbService $tmdb): void
    {
        $today = now()->toDateString();
        $position = 0;
        $processedTmdbIds = [];

        Log::info("SyncGenreMoviesJob: Starting sync for genre {$this->genreId}, {$this->pages} pages");

        for ($page = 1; $page <= $this->pages; $page++) {
            $data = $tmdb->discoverMoviesByGenre($this->genreId, $page);
            $movies = $data['movies'];

            if ($movies->isEmpty()) {
                Log::warning("SyncGenreMoviesJob: No movies returned for genre {$this->genreId}, page {$page}");

                continue;
            }

            foreach ($movies as $movieData) {
                // Skip duplicates within this sync run
                if (in_array($movieData['id'], $processedTmdbIds, true)) {
                    continue;
                }
                $processedTmdbIds[] = $movieData['id'];
                $position++;

                // Upsert the movie
                $movie = Movie::updateOrCreate(
                    ['tmdb_id' => $movieData['id']],
                    [
                        'title' => $movieData['title'],
                        'poster_path' => $movieData['poster_path'],
                        'backdrop_path' => $movieData['backdrop_path'],
                        'overview' => $movieData['overview'],
                        'release_date' => $movieData['release_date'] ?: null,
                        'vote_average' => $movieData['vote_average'],
                        'tmdb_popularity' => $movieData['popularity'],
                        'genre_ids' => $movieData['genre_ids'],
                        'source' => 'search',
                    ]
                );

                // Create or update genre snapshot for today
                GenreSnapshot::updateOrCreate(
                    [
                        'movie_id' => $movie->id,
                        'genre_id' => $this->genreId,
                        'snapshot_date' => $today,
                    ],
                    [
                        'position' => $position,
                        'page' => $page,
                    ]
                );
            }

            Log::info("SyncGenreMoviesJob: Synced genre {$this->genreId} page {$page} with {$movies->count()} movies");

            // Be nice to the API - small delay between pages
            if ($page < $this->pages) {
                usleep(250000); // 250ms
            }
        }

        Log::info("SyncGenreMoviesJob: Completed sync for genre {$this->genreId}, {$position} total movies");
    }
}
