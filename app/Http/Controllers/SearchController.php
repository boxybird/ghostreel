<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Genre;
use App\Models\Movie;
use App\Services\MovieRepository;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
        private readonly TmdbService $tmdbService,
    ) {}

    /**
     * Search movies: local-first with TMDB API fallback.
     * Returns Blade fragment for HTMX.
     */
    public function search(SearchRequest $request): View
    {
        $query = $request->validated('q');
        $page = $request->validated('page', 1);

        // First, search local database
        $localResults = $this->movieRepo->searchMovies($query, 20);

        // If local results are insufficient, fallback to TMDB API
        $tmdbResults = collect();
        if ($localResults->count() < 5) {
            $tmdbResults = $this->tmdbService->searchMovies($query, $page);

            // Persist TMDB results to local database
            $this->seedMoviesToDatabase($tmdbResults);
        }

        // Merge results, prioritizing local, and dedupe by tmdb_id
        $movies = $this->mergeResults($localResults, $tmdbResults);

        // Get database IDs for movies
        $tmdbIds = $movies->pluck('id')->toArray();
        $dbIds = $this->movieRepo->getDbIds($tmdbIds);

        // Transform for view
        $results = $movies->map(fn (array $movie): array => [
            ...$movie,
            'db_id' => $dbIds[$movie['id']] ?? null,
            'poster_url' => TmdbService::posterUrl($movie['poster_path']),
        ]);

        return view('heatmap.partials.search-results', [
            'results' => $results,
            'query' => $query,
            'page' => $page,
            'hasMore' => $tmdbResults->count() >= 20,
        ]);
    }

    /**
     * Seed movies from TMDB response to local database.
     *
     * @param  Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, popularity: float, genre_ids: array<int, int>}>  $movies
     */
    private function seedMoviesToDatabase(Collection $movies): void
    {
        foreach ($movies as $movieData) {
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
            if (! empty($movieData['genre_ids'])) {
                $genreIds = Genre::whereIn('tmdb_id', $movieData['genre_ids'])->pluck('id');
                $movie->genres()->sync($genreIds);
            }
        }
    }

    /**
     * Merge local and TMDB results, deduping by tmdb_id.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Movie>  $localResults
     * @param  Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, popularity: float, genre_ids: array<int, int>}>  $tmdbResults
     * @return Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>
     */
    private function mergeResults(\Illuminate\Database\Eloquent\Collection $localResults, Collection $tmdbResults): Collection
    {
        // Transform local results to array format
        $local = $localResults->map(fn (Movie $movie): array => [
            'id' => $movie->tmdb_id,
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'backdrop_path' => $movie->backdrop_path,
            'overview' => $movie->overview ?? '',
            'release_date' => $movie->release_date?->format('Y-m-d') ?? '',
            'vote_average' => (float) $movie->vote_average,
        ]);

        // Get IDs from local results for deduplication
        $localIds = $local->pluck('id')->toArray();

        // Filter TMDB results to exclude already-present movies
        $uniqueTmdb = $tmdbResults->filter(fn (array $movie): bool => ! in_array($movie['id'], $localIds, true));

        // Merge: local first, then unique TMDB results
        return $local->concat($uniqueTmdb)->take(20);
    }
}
