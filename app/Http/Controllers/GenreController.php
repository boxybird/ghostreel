<?php

namespace App\Http\Controllers;

use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $clickCounts = $this->getClickCounts($movies->pluck('id')->toArray());

        $moviesWithData = $movies->map(function (array $movie) use ($clickCounts): array {
            return [
                ...$movie,
                'poster_url' => TmdbService::posterUrl($movie['poster_path']),
                'click_count' => $clickCounts[$movie['id']] ?? 0,
            ];
        });

        return view('genre.movies', [
            'movies' => $moviesWithData,
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
}
