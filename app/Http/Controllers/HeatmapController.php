<?php

namespace App\Http\Controllers;

use App\Actions\TransformMovieForDisplayAction;
use App\Services\MovieService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HeatmapController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
        private readonly TransformMovieForDisplayAction $transformMovie,
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

        $movies = $this->transformMovie->collection($trendingMovies, $clickCounts);

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
