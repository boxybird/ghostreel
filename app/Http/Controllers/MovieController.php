<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;

class MovieController extends Controller
{
    public function __construct(
        private readonly TmdbService $tmdbService,
    ) {}

    /**
     * Display the movie detail page.
     */
    public function show(Movie $movie): View
    {
        // Fetch extended details from TMDB
        $tmdbDetails = $this->tmdbService->getMovieDetails($movie->tmdb_id);

        // Get community click count for this movie (last 24 hours)
        $clickCount = MovieClick::query()
            ->where('tmdb_movie_id', $movie->tmdb_id)
            ->where('clicked_at', '>=', now()->subDay())
            ->count();

        // Get total all-time click count
        $totalClickCount = MovieClick::query()
            ->where('tmdb_movie_id', $movie->tmdb_id)
            ->count();

        // Build full image URLs
        $posterUrl = TmdbService::posterUrl($movie->poster_path);
        $backdropUrl = TmdbService::backdropUrl($movie->backdrop_path);

        // Transform cast with profile URLs
        $cast = [];
        if ($tmdbDetails !== null && $tmdbDetails['cast'] !== []) {
            $cast = collect($tmdbDetails['cast'])->map(fn (array $person): array => [
                ...$person,
                'profile_url' => TmdbService::profileUrl($person['profile_path']),
            ])->all();
        }

        // Transform similar movies with poster URLs and db_id lookup
        $similarMovies = [];
        if ($tmdbDetails !== null && $tmdbDetails['similar'] !== []) {
            $similarTmdbIds = collect($tmdbDetails['similar'])->pluck('id')->toArray();
            $existingMovies = Movie::whereIn('tmdb_id', $similarTmdbIds)
                ->pluck('id', 'tmdb_id')
                ->toArray();

            $similarMovies = collect($tmdbDetails['similar'])->map(function (array $m) use ($existingMovies): array {
                return [
                    ...$m,
                    'poster_url' => TmdbService::posterUrl($m['poster_path']),
                    'db_id' => $existingMovies[$m['id']] ?? null,
                ];
            })->all();
        }

        return view('movies.show', [
            'movie' => $movie,
            'details' => $tmdbDetails,
            'posterUrl' => $posterUrl,
            'backdropUrl' => $backdropUrl,
            'clickCount' => $clickCount,
            'totalClickCount' => $totalClickCount,
            'cast' => $cast,
            'similarMovies' => $similarMovies,
        ]);
    }
}
