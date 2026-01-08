<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogClickRequest;
use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly TmdbService $tmdbService,
    ) {}

    /**
     * Display the main heatmap view with trending movies.
     */
    public function index(Request $request): View
    {
        $trendingData = $this->tmdbService->getTrendingMovies(page: 1);
        $trendingMovies = $trendingData['movies'];

        // Auto-seed trending movies to local database
        $this->seedMoviesToDatabase($trendingMovies, 'trending');

        $clickCounts = $this->getClickCounts($trendingMovies->pluck('id')->toArray());

        $movies = $trendingMovies->map(function (array $movie) use ($clickCounts): array {
            return [
                ...$movie,
                'poster_url' => TmdbService::posterUrl($movie['poster_path']),
                'click_count' => $clickCounts[$movie['id']] ?? 0,
            ];
        });

        $recentViews = $this->getRecentViews();
        $genres = $this->tmdbService->getGenres();

        return view('heatmap.index', [
            'movies' => $movies,
            'recentViews' => $recentViews,
            'genres' => $genres,
            'currentPage' => $trendingData['page'],
            'totalPages' => $trendingData['total_pages'],
            'hasMorePages' => $trendingData['page'] < $trendingData['total_pages'],
        ]);
    }

    /**
     * Load more trending movies (HTMX partial).
     */
    public function trending(Request $request): View
    {
        $page = (int) $request->input('page', 1);
        $trendingData = $this->tmdbService->getTrendingMovies(page: $page);
        $trendingMovies = $trendingData['movies'];

        // Auto-seed trending movies to local database
        $this->seedMoviesToDatabase($trendingMovies, 'trending');

        $clickCounts = $this->getClickCounts($trendingMovies->pluck('id')->toArray());

        $movies = $trendingMovies->map(function (array $movie) use ($clickCounts): array {
            return [
                ...$movie,
                'poster_url' => TmdbService::posterUrl($movie['poster_path']),
                'click_count' => $clickCounts[$movie['id']] ?? 0,
            ];
        });

        return view('heatmap.partials.movie-cards', [
            'movies' => $movies,
            'currentPage' => $trendingData['page'],
            'totalPages' => $trendingData['total_pages'],
            'hasMorePages' => $trendingData['page'] < $trendingData['total_pages'],
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
