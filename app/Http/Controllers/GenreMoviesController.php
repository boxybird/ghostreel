<?php

namespace App\Http\Controllers;

use App\Actions\TransformMovieForDisplayAction;
use App\Models\Genre;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreMoviesController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
        private readonly TransformMovieForDisplayAction $transformMovie,
    ) {}

    public function index(Request $request, int $genreId): View
    {
        $page = (int) $request->input('page', 1);

        // Find genre by TMDB ID
        $genre = $this->movieService->getGenreByTmdbId($genreId);

        if (! $genre instanceof Genre) {
            abort(404, 'Genre not found');
        }

        // Ensure we have data (dispatches job if needed)
        $this->movieService->ensureGenreDataAvailable($genre);

        $paginator = $this->movieService->getMoviesByGenre($genre, $page);
        $genreMovies = $paginator->items();

        $genreName = $genre->name;

        $tmdbIds = collect($genreMovies)->pluck('tmdb_id')->toArray();
        $clickCounts = $this->movieService->getClickCounts($tmdbIds);

        $moviesWithData = $this->transformMovie->collection($genreMovies, $clickCounts);

        $genres = $this->movieService->getAllGenres();

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
}
