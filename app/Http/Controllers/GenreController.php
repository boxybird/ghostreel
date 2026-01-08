<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GenreController extends Controller
{
    public function __construct(
        private readonly TmdbService $tmdbService,
    ) {}

    /**
     * Get list of all genres (JSON for chips).
     */
    public function index(): JsonResponse
    {
        $genres = $this->tmdbService->getGenres();

        return response()->json([
            'genres' => $genres,
        ]);
    }

    /**
     * Browse movies by genre (returns partial for HTMX).
     */
    public function show(Request $request, int $genreId): View
    {
        $page = (int) $request->input('page', 1);
        $genreData = $this->tmdbService->discoverMoviesByGenre($genreId, $page);
        $movies = $genreData['movies'];

        // Find genre name from cached genres
        $genres = $this->tmdbService->getGenres();
        $genre = $genres->firstWhere('id', $genreId);
        $genreName = $genre['name'] ?? 'Unknown';

        // Auto-seed genre movies to local database (use 'search' source for genre discovery)
        $this->seedMoviesToDatabase($movies, 'search');

        $clickCounts = $this->getClickCounts($movies->pluck('id')->toArray());
        $dbIds = $this->getDbIds($movies->pluck('id')->toArray());

        $moviesWithData = $movies->map(function (array $movie) use ($clickCounts, $dbIds): array {
            return [
                ...$movie,
                'db_id' => $dbIds[$movie['id']] ?? null,
                'poster_url' => TmdbService::posterUrl($movie['poster_path']),
                'click_count' => $clickCounts[$movie['id']] ?? 0,
            ];
        });

        return view('genre.movies', [
            'movies' => $moviesWithData,
            'genres' => $genres,
            'genreId' => $genreId,
            'genreName' => $genreName,
            'currentPage' => $genreData['page'],
            'totalPages' => min($genreData['total_pages'], 500), // TMDB limits to 500 pages
            'hasMorePages' => $genreData['page'] < min($genreData['total_pages'], 500),
        ]);
    }

    /**
     * Get click counts for given movie IDs (last 24 hours).
     *
     * @param  array<int>  $movieIds
     * @return array<int, int>
     */
    private function getClickCounts(array $movieIds): array
    {
        if ($movieIds === []) {
            return [];
        }

        return MovieClick::query()
            ->selectRaw('tmdb_movie_id, COUNT(*) as click_count')
            ->whereIn('tmdb_movie_id', $movieIds)
            ->where('clicked_at', '>=', now()->subDay())
            ->groupBy('tmdb_movie_id')
            ->pluck('click_count', 'tmdb_movie_id')
            ->toArray();
    }

    /**
     * Get database IDs for given TMDB movie IDs.
     *
     * @param  array<int>  $tmdbIds
     * @return array<int, int>
     */
    private function getDbIds(array $tmdbIds): array
    {
        if ($tmdbIds === []) {
            return [];
        }

        return Movie::query()
            ->whereIn('tmdb_id', $tmdbIds)
            ->pluck('id', 'tmdb_id')
            ->toArray();
    }

    /**
     * Seed movies from TMDB response to local database.
     *
     * @param  Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>  $movies
     */
    private function seedMoviesToDatabase(Collection $movies, string $source): void
    {
        foreach ($movies as $movie) {
            Movie::updateOrCreate(
                ['tmdb_id' => $movie['id']],
                [
                    'title' => $movie['title'],
                    'poster_path' => $movie['poster_path'],
                    'backdrop_path' => $movie['backdrop_path'],
                    'overview' => $movie['overview'],
                    'release_date' => $movie['release_date'] ?: null,
                    'vote_average' => $movie['vote_average'],
                    'source' => $source,
                ]
            );
        }
    }
}
