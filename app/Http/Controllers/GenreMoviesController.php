<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Services\MovieService;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreMoviesController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
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
