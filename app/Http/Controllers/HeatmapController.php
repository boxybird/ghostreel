<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\MovieService;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    /**
     * Display the main heatmap view with trending movies.
     */
    public function index(Request $request): View
    {
        // Ensure we have data (dispatches job if needed)
        $this->movieService->ensureTrendingDataAvailable();

        $paginator = $this->movieService->getTrendingMovies(page: 1);
        $trendingMovies = $paginator->items();

        $tmdbIds = collect($trendingMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->movieService->getClickCounts($tmdbIds);

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

        $recentViews = $this->movieService->getRecentViews();
        $genres = $this->movieService->getAllGenres();

        return view('heatmap.index', [
            'movies' => $movies,
            'recentViews' => $recentViews,
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'hasMorePages' => $paginator->hasMorePages(),
        ]);
    }
}
