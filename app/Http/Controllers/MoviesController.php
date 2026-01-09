<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\MovieRepository;
use App\Services\TmdbService;
use Illuminate\View\View;

class MoviesController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
        private readonly TmdbService $tmdbService,
    ) {}

    public function show(Movie $movie): View
    {
        // Ensure movie details (cast, etc.) are available
        $this->movieRepo->ensureMovieDetailsAvailable($movie);

        $movie = $this->movieRepo->getMovieWithDetails($movie);

        // Get extended details from TMDB API if needed
        $details = $this->tmdbService->getMovieDetails($movie->tmdb_id);

        // Transform cast - use DB data if available, fallback to TMDB API data
        $cast = $movie->castMembers->isNotEmpty()
            ? $movie->castMembers->take(12)->map(fn ($member): array => [
                'name' => $member->person->name,
                'character' => $member->character,
                'profile_url' => TmdbService::posterUrl($member->person->profile_path, 'w185'),
            ])
            : collect($details['cast'] ?? [])->take(12)->map(fn (array $member): array => [
                'name' => $member['name'],
                'character' => $member['character'],
                'profile_url' => TmdbService::posterUrl($member['profile_path'], 'w185'),
            ]);

        // Get similar movies
        $similarMovies = collect($details['similar'] ?? [])->take(10)->map(fn (array $m): array => [
            'id' => $m['id'],
            'db_id' => $this->movieRepo->getMovieByTmdbId($m['id'])?->id,
            'title' => $m['title'],
            'poster_path' => $m['poster_path'],
            'poster_url' => TmdbService::posterUrl($m['poster_path']),
            'vote_average' => $m['vote_average'],
        ]);

        // Get click statistics
        $clickCount = MovieClick::where('tmdb_movie_id', $movie->tmdb_id)
            ->where('clicked_at', '>=', now()->subDay())
            ->count();

        $totalClickCount = MovieClick::where('tmdb_movie_id', $movie->tmdb_id)->count();

        return view('movies.show', [
            'movie' => $movie,
            'posterUrl' => TmdbService::posterUrl($movie->poster_path, 'w500'),
            'backdropUrl' => TmdbService::posterUrl($movie->backdrop_path, 'original'),
            'cast' => $cast,
            'details' => $details,
            'similarMovies' => $similarMovies,
            'clickCount' => $clickCount,
            'totalClickCount' => $totalClickCount,
        ]);
    }
}
