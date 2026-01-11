<?php

namespace App\Actions;

use App\Services\MovieService;
use App\Services\TmdbService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class SearchMoviesAction
{
    public function __construct(
        private readonly MovieService $movieService,
        private readonly TmdbService $tmdbService,
        private readonly SeedMovieFromTmdbAction $seedMovie,
        private readonly TransformMovieForDisplayAction $transformMovie,
    ) {}

    /**
     * Search movies locally first, with TMDB API fallback.
     *
     * @return array{results: Collection<int, array<string, mixed>>, has_more: bool}
     */
    public function handle(string $query, int $page = 1, int $localLimit = 20, int $localThreshold = 5): array
    {
        // First, search local database
        $localResults = $this->movieService->searchMovies($query, $localLimit);

        // If local results are insufficient, fallback to TMDB API
        $tmdbResults = collect();
        if ($localResults->count() < $localThreshold) {
            $tmdbResults = $this->tmdbService->searchMovies($query, $page);

            // Persist TMDB results to local database
            foreach ($tmdbResults as $movieData) {
                $this->seedMovie->handle($movieData, 'search');
            }
        }

        // Merge and dedupe results
        $results = $this->mergeResults($localResults, $tmdbResults);

        // Get database IDs for TMDB results that may not have db_id yet
        $tmdbIds = $results->pluck('id')->toArray();
        $dbIds = $this->movieService->getDbIds($tmdbIds);

        // Ensure all results have db_id and poster_url
        $finalResults = $results->map(fn (array $movie): array => [
            ...$movie,
            'db_id' => $movie['db_id'] ?? $dbIds[$movie['id']] ?? null,
            'poster_url' => $movie['poster_url'] ?? TmdbService::posterUrl($movie['poster_path'] ?? null),
        ]);

        return [
            'results' => $finalResults,
            'has_more' => $tmdbResults->count() >= 20,
        ];
    }

    /**
     * Merge local and TMDB results, deduping by tmdb_id.
     *
     * @param  Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>  $tmdbResults
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeResults(EloquentCollection $localResults, Collection $tmdbResults): Collection
    {
        // Transform local results using the action
        $local = $this->transformMovie->collection($localResults);

        // Get IDs from local results for deduplication
        $localIds = $local->pluck('id')->toArray();

        // Filter TMDB results to exclude already-present movies
        $uniqueTmdb = $tmdbResults->filter(fn (array $movie): bool => ! in_array($movie['id'], $localIds, true));

        // Merge: local first, then unique TMDB results
        return $local->concat($uniqueTmdb)->take(20);
    }
}
