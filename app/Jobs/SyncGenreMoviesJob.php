<?php

namespace App\Jobs;

use App\Models\Genre;
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
        // Look up the Genre by TMDB ID to get the database ID
        $genre = Genre::where('tmdb_id', $this->genreId)->first();

        if ($genre === null) {
            Log::warning("SyncGenreMoviesJob: Genre with TMDB ID {$this->genreId} not found in database");

            return;
        }

        $today = now()->toDateString();
        $position = 0;
        $processedTmdbIds = [];

        Log::info("SyncGenreMoviesJob: Starting sync for genre {$genre->name} (ID: {$genre->id}), {$this->pages} pages");

        for ($page = 1; $page <= $this->pages; $page++) {
            $data = $tmdb->discoverMoviesByGenre($this->genreId, $page);
            $movies = $data['movies'];

            if ($movies->isEmpty()) {
                Log::warning("SyncGenreMoviesJob: No movies returned for genre {$genre->name}, page {$page}");

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
                        'source' => 'search',
                    ]
                );

                // Sync genres via pivot table
                $this->syncGenres($movie, $movieData['genre_ids']);

                // Create or update genre snapshot for today (using genre.id, not tmdb_id)
                GenreSnapshot::updateOrCreate(
                    [
                        'movie_id' => $movie->id,
                        'genre_id' => $genre->id,
                        'snapshot_date' => $today,
                    ],
                    [
                        'position' => $position,
                        'page' => $page,
                    ]
                );
            }

            Log::info("SyncGenreMoviesJob: Synced genre {$genre->name} page {$page} with {$movies->count()} movies");

            // Be nice to the API - small delay between pages
            if ($page < $this->pages) {
                usleep(250000); // 250ms
            }
        }

        Log::info("SyncGenreMoviesJob: Completed sync for genre {$genre->name}, {$position} total movies");
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
