<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\MovieRepository;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    /**
     * Get list of all genres (JSON for chips).
     */
    public function index(): JsonResponse
    {
        $genres = $this->movieRepo->getAllGenres();

        return response()->json([
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
        ]);
    }

    /**
     * Browse movies by genre (returns partial for HTMX).
     */
    public function show(Request $request, int $genreId): View
    {
        $page = (int) $request->input('page', 1);

        // Find genre by TMDB ID
        $genre = $this->movieRepo->getGenreByTmdbId($genreId);

        if (!$genre instanceof \App\Models\Genre) {
            abort(404, 'Genre not found');
        }

        // Ensure we have data (dispatches job if needed)
        $this->movieRepo->ensureGenreDataAvailable($genre);

        $paginator = $this->movieRepo->getMoviesByGenre($genre, $page);
        $genreMovies = $paginator->items();

        $genreName = $genre->name;

        $tmdbIds = collect($genreMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->getClickCounts($tmdbIds);

        $moviesWithData = collect($genreMovies)->map(function (Movie $movie) use ($clickCounts): array {
            return [
                'id' => $movie->tmdb_id,
                'db_id' => $movie->id,
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
                'backdrop_path' => $movie->backdrop_path,
                'overview' => $movie->overview ?? '',
                'release_date' => $movie->release_date?->format('Y-m-d') ?? '',
                'vote_average' => (float) $movie->vote_average,
                'poster_url' => TmdbService::posterUrl($movie->poster_path),
                'click_count' => $clickCounts[$movie->tmdb_id] ?? 0,
            ];
        });

        $genres = $this->movieRepo->getAllGenres();

        return view('genre.movies', [
            'movies' => $moviesWithData,
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
            'genreId' => $genreId,
            'genreName' => $genreName,
            'currentPage' => $paginator->currentPage(),
            'totalPages' => min($paginator->lastPage(), 500), // TMDB limits to 500 pages
            'hasMorePages' => $paginator->hasMorePages() && $paginator->currentPage() < 500,
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
