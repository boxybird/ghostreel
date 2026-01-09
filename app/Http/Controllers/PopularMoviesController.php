<?php

namespace App\Http\Controllers;

use App\Services\MovieRepository;
use Illuminate\View\View;

class PopularMoviesController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    public function index(): View
    {
        $popularMovies = $this->movieRepo->getPopularMovies();
        $recentViews = $this->movieRepo->getRecentViews();

        return view('popular.index', [
            'movies' => $popularMovies,
            'recentViews' => $recentViews,
        ]);
    }
}
