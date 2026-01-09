<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\MovieService;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrendingMoviesController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(Request $request): View
    {
        $page = (int) $request->input('page', 1);

        $paginator = $this->movieService->getTrendingMovies(page: $page);
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
