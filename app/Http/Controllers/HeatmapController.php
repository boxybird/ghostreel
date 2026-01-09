<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogClickRequest;
use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\MovieRepository;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    /**
     * Display the main heatmap view with trending movies.
     */
    public function index(Request $request): View
    {
        // Ensure we have data (dispatches job if needed)
        $this->movieRepo->ensureTrendingDataAvailable();

        $paginator = $this->movieRepo->getTrendingMovies(page: 1);
        $trendingMovies = $paginator->items();

        $tmdbIds = collect($trendingMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->getClickCounts($tmdbIds);

        $movies = collect($trendingMovies)->map(function (Movie $movie) use ($clickCounts): array {
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

        $recentViews = $this->getRecentViews();
        $genres = $this->movieRepo->getAllGenres();

        return view('heatmap.index', [
            'movies' => $movies,
            'recentViews' => $recentViews,
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'hasMorePages' => $paginator->hasMorePages(),
        ]);
    }

    /**
     * Load more trending movies (HTMX partial).
     */
    public function trending(Request $request): View
    {
        $page = (int) $request->input('page', 1);

        $paginator = $this->movieRepo->getTrendingMovies(page: $page);
        $trendingMovies = $paginator->items();

        $tmdbIds = collect($trendingMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->getClickCounts($tmdbIds);

        $movies = collect($trendingMovies)->map(function (Movie $movie) use ($clickCounts): array {
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

        // Include genres for OOB swap when returning to page 1 (filter cleared)
        $genres = null;
        if ($page === 1) {
            $genreModels = $this->movieRepo->getAllGenres();
            $genres = $genreModels->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]);
        }

        return view('heatmap.partials.movie-cards', [
            'movies' => $movies,
            'genres' => $genres,
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'hasMorePages' => $paginator->hasMorePages(),
        ]);
    }

    /**
     * Log a movie click from the current visitor.
     */
    public function logClick(LogClickRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $click = MovieClick::create([
            'ip_address' => $request->ip(),
            'tmdb_movie_id' => $validated['tmdb_movie_id'],
            'movie_title' => $validated['movie_title'],
            'poster_path' => $validated['poster_path'] ?? null,
            'clicked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'click_id' => $click->id,
            'recent_views' => $this->getRecentViews(),
        ]);
    }

    /**
     * Get recent views for the sidebar (API endpoint).
     */
    public function recentViews(): JsonResponse
    {
        return response()->json([
            'recent_views' => $this->getRecentViews(),
        ]);
    }

    /**
     * Get heatmap aggregation data for all movies.
     */
    public function heatmapData(): JsonResponse
    {
        $clickCounts = MovieClick::query()
            ->selectRaw('tmdb_movie_id, COUNT(*) as click_count')
            ->where('clicked_at', '>=', now()->subDay())
            ->groupBy('tmdb_movie_id')
            ->pluck('click_count', 'tmdb_movie_id');

        return response()->json([
            'heatmap' => $clickCounts,
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
     * Get the most recent movie views for the sidebar.
     *
     * @return Collection<int, array{id: int, tmdb_movie_id: int, movie_title: string, poster_url: ?string, clicked_at: string}>
     */
    private function getRecentViews(): Collection
    {
        return MovieClick::query()
            ->select(['id', 'tmdb_movie_id', 'movie_title', 'poster_path', 'clicked_at'])
            ->orderByDesc('clicked_at')
            ->limit(10)
            ->get()
            ->map(fn (MovieClick $click): array => [
                'id' => $click->id,
                'tmdb_movie_id' => $click->tmdb_movie_id,
                'movie_title' => $click->movie_title,
                'poster_url' => TmdbService::posterUrl($click->poster_path, 'w185'),
                'clicked_at' => $click->clicked_at->diffForHumans(),
            ]);
    }
}
