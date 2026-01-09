<?php

namespace App\Services;

use App\Jobs\SyncAllGenresJob;
use App\Jobs\SyncGenreMoviesJob;
use App\Jobs\SyncMovieDetailsJob;
use App\Jobs\SyncTrendingMoviesJob;
use App\Models\Genre;
use App\Models\GenreSnapshot;
use App\Models\Movie;
use App\Models\TrendingSnapshot;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MovieRepository
{
    private const ITEMS_PER_PAGE = 20;

    /**
     * Get trending movies from the database, ordered by snapshot position.
     * Falls back to tmdb_popularity sort if no snapshot exists for the date.
     *
     * @return LengthAwarePaginator<int, Movie>
     */
    public function getTrendingMovies(int $page = 1, ?Carbon $date = null, string $listType = 'trending_day'): LengthAwarePaginator
    {
        $date = $date ?? now();

        // Check if we have a snapshot for this date
        if ($this->hasTrendingSnapshotFor($date, $listType)) {
            return Movie::query()
                ->select('movies.*')
                ->join('trending_snapshots', 'movies.id', '=', 'trending_snapshots.movie_id')
                ->where('trending_snapshots.list_type', $listType)
                ->whereDate('trending_snapshots.snapshot_date', $date)
                ->orderBy('trending_snapshots.position')
                ->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $page);
        }

        // Fallback: return movies sorted by popularity
        return Movie::query()
            ->whereNotNull('tmdb_popularity')
            ->orderByDesc('tmdb_popularity')
            ->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $page);
    }

    /**
     * Get movies by genre from the database, ordered by snapshot position.
     * Falls back to tmdb_popularity sort if no snapshot exists for the date.
     *
     * @return LengthAwarePaginator<int, Movie>
     */
    public function getMoviesByGenre(int $genreId, int $page = 1, ?Carbon $date = null): LengthAwarePaginator
    {
        $date = $date ?? now();

        // Check if we have a snapshot for this genre/date
        if ($this->hasGenreSnapshotFor($genreId, $date)) {
            return Movie::query()
                ->select('movies.*')
                ->join('genre_snapshots', 'movies.id', '=', 'genre_snapshots.movie_id')
                ->where('genre_snapshots.genre_id', $genreId)
                ->whereDate('genre_snapshots.snapshot_date', $date)
                ->orderBy('genre_snapshots.position')
                ->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $page);
        }

        // Fallback: return movies with this genre_id sorted by popularity
        return Movie::query()
            ->whereNotNull('genre_ids')
            ->whereRaw("json_extract(genre_ids, '$') LIKE ?", ["%{$genreId}%"])
            ->orderByDesc('tmdb_popularity')
            ->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $page);
    }

    /**
     * Search movies by title in the local database.
     *
     * @return Collection<int, Movie>
     */
    public function searchMovies(string $query, int $limit = 20): Collection
    {
        return Movie::query()
            ->search($query)
            ->orderByDesc('tmdb_popularity')
            ->limit($limit)
            ->get();
    }

    /**
     * Find a movie by its TMDB ID.
     */
    public function getMovieByTmdbId(int $tmdbId): ?Movie
    {
        return Movie::where('tmdb_id', $tmdbId)->first();
    }

    /**
     * Get a movie with its full details (cast, etc.).
     */
    public function getMovieWithDetails(Movie $movie): Movie
    {
        return $movie->load(['castMembers.person']);
    }

    /**
     * Check if a trending snapshot exists for the given date.
     */
    public function hasTrendingSnapshotFor(Carbon $date, string $listType = 'trending_day'): bool
    {
        return TrendingSnapshot::query()
            ->where('list_type', $listType)
            ->whereDate('snapshot_date', $date)
            ->exists();
    }

    /**
     * Check if a genre snapshot exists for the given genre and date.
     */
    public function hasGenreSnapshotFor(int $genreId, Carbon $date): bool
    {
        return GenreSnapshot::query()
            ->where('genre_id', $genreId)
            ->whereDate('snapshot_date', $date)
            ->exists();
    }

    /**
     * Check if a movie has synced details.
     */
    public function hasMovieDetails(Movie $movie): bool
    {
        return $movie->details_synced_at !== null;
    }

    /**
     * Ensure trending data is available, dispatching a sync job if needed.
     */
    public function ensureTrendingDataAvailable(string $listType = 'trending_day'): void
    {
        if (! $this->hasTrendingSnapshotFor(now(), $listType)) {
            SyncTrendingMoviesJob::dispatch(pages: 5, listType: $listType);
        }
    }

    /**
     * Ensure genre data is available, dispatching sync jobs if needed.
     */
    public function ensureGenreDataAvailable(int $genreId): void
    {
        // Ensure genres table is populated
        if (Genre::count() === 0) {
            SyncAllGenresJob::dispatch(pagesPerGenre: 5, syncMovies: false);
        }

        if (! $this->hasGenreSnapshotFor($genreId, now())) {
            SyncGenreMoviesJob::dispatch($genreId, pages: 5);
        }
    }

    /**
     * Ensure movie details are available, dispatching a sync job if needed.
     */
    public function ensureMovieDetailsAvailable(Movie $movie): void
    {
        if (! $this->hasMovieDetails($movie)) {
            SyncMovieDetailsJob::dispatch($movie->id);
        }
    }

    /**
     * Get all genres from the database.
     *
     * @return Collection<int, Genre>
     */
    public function getAllGenres(): Collection
    {
        return Genre::orderBy('name')->get();
    }

    /**
     * Get a genre by its TMDB ID.
     */
    public function getGenreByTmdbId(int $tmdbId): ?Genre
    {
        return Genre::where('tmdb_id', $tmdbId)->first();
    }

    /**
     * Get the total pages available for trending movies on a given date.
     */
    public function getTrendingTotalPages(Carbon $date, string $listType = 'trending_day'): int
    {
        $count = TrendingSnapshot::query()
            ->where('list_type', $listType)
            ->whereDate('snapshot_date', $date)
            ->count();

        return max(1, (int) ceil($count / self::ITEMS_PER_PAGE));
    }

    /**
     * Get the total pages available for genre movies on a given date.
     */
    public function getGenreTotalPages(int $genreId, Carbon $date): int
    {
        $count = GenreSnapshot::query()
            ->where('genre_id', $genreId)
            ->whereDate('snapshot_date', $date)
            ->count();

        return max(1, (int) ceil($count / self::ITEMS_PER_PAGE));
    }

    /**
     * Get database IDs for given TMDB movie IDs.
     *
     * @param  array<int>  $tmdbIds
     * @return array<int, int>
     */
    public function getDbIds(array $tmdbIds): array
    {
        if ($tmdbIds === []) {
            return [];
        }

        return Movie::query()
            ->whereIn('tmdb_id', $tmdbIds)
            ->pluck('id', 'tmdb_id')
            ->toArray();
    }
}
