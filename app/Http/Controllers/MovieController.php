<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\MovieRepository;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;

class MovieController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
        private readonly TmdbService $tmdbService,
    ) {}

    /**
     * Display the movie detail page.
     */
    public function show(Movie $movie): View
    {
        // Ensure movie details are synced (dispatches job if needed)
        $this->movieRepo->ensureMovieDetailsAvailable($movie);

        // Reload to get any freshly synced data
        $movie->refresh();

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

        // Get cast from database if available
        $cast = [];
        if ($movie->hasDetails()) {
            $movie->load(['castMembers.person']);
            $cast = $movie->castMembers
                ->sortBy('order')
                ->map(fn ($cm): array => [
                    'id' => $cm->person->tmdb_id,
                    'name' => $cm->person->name,
                    'character' => $cm->character ?? '',
                    'profile_path' => $cm->person->profile_path,
                    'profile_url' => TmdbService::profileUrl($cm->person->profile_path),
                ])
                ->values()
                ->all();
        } else {
            // Fallback to TMDB API if details not yet synced
            $tmdbDetails = $this->tmdbService->getMovieDetails($movie->tmdb_id);
            if ($tmdbDetails !== null && $tmdbDetails['cast'] !== []) {
                $cast = collect($tmdbDetails['cast'])->map(fn (array $person): array => [
                    ...$person,
                    'profile_url' => TmdbService::profileUrl($person['profile_path']),
                ])->all();
            }
        }

        // Get similar movies
        $similarMovies = [];
        if ($movie->similar_tmdb_ids !== null && $movie->similar_tmdb_ids !== []) {
            $similarMovies = $movie->similarMovies->map(function (Movie $m): array {
                return [
                    'id' => $m->tmdb_id,
                    'title' => $m->title,
                    'poster_path' => $m->poster_path,
                    'poster_url' => TmdbService::posterUrl($m->poster_path),
                    'db_id' => $m->id,
                ];
            })->all();
        } elseif (! $movie->hasDetails()) {
            // Fallback to TMDB API if details not yet synced
            $tmdbDetails = $tmdbDetails ?? $this->tmdbService->getMovieDetails($movie->tmdb_id);
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
        }

        // Build details array for view (combines DB and potentially fallback data)
        $details = [
            'id' => $movie->tmdb_id,
            'title' => $movie->title,
            'tagline' => $movie->tagline,
            'runtime' => $movie->runtime,
            'genres' => $movie->genre_ids !== null
                ? $this->movieRepo->getAllGenres()
                    ->whereIn('tmdb_id', $movie->genre_ids)
                    ->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name])
                    ->values()
                    ->all()
                : [],
            'cast' => $cast,
            'similar' => $similarMovies,
        ];

        return view('movies.show', [
            'movie' => $movie,
            'details' => $details,
            'posterUrl' => $posterUrl,
            'backdropUrl' => $backdropUrl,
            'clickCount' => $clickCount,
            'totalClickCount' => $totalClickCount,
            'cast' => $cast,
            'similarMovies' => $similarMovies,
        ]);
    }
}
