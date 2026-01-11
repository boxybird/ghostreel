<?php

namespace App\Http\Controllers;

use App\Actions\TransformMovieForDisplayAction;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrendingMoviesController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
        private readonly TransformMovieForDisplayAction $transformMovie,
    ) {}

    public function index(Request $request): View
    {
        $page = (int) $request->input('page', 1);

        $paginator = $this->movieService->getTrendingMovies(page: $page);
        $trendingMovies = $paginator->items();

        $tmdbIds = collect($trendingMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->movieService->getClickCounts($tmdbIds);

        $movies = $this->transformMovie->collection($trendingMovies, $clickCounts);

        // Include genres for OOB swap when returning to page 1 (filter cleared)
        $genres = null;
        if ($page === 1) {
            $genreModels = $this->movieService->getAllGenres();
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
}
