<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use Illuminate\View\View;

class PopularMoviesController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(): View
    {
        $popularMovies = $this->movieService->getPopularMovies();
        $recentViews = $this->movieService->getRecentViews();

        return view('popular.index', [
            'movies' => $popularMovies,
            'recentViews' => $recentViews,
        ]);
    }
}
