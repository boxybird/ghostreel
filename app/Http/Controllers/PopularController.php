<?php

namespace App\Http\Controllers;

use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PopularController extends Controller
{
    /**
     * Display movies ranked by click count within the app.
     */
    public function index(): View
    {
        $popularMovies = $this->getPopularMovies();
        $recentViews = $this->getRecentViews();

        return view('popular.index', [
            'movies' => $popularMovies,
            'recentViews' => $recentViews,
        ]);
    }

    /**
     * Get movies ordered by click count (most clicked first).
     *
     * @return Collection<int, array{tmdb_movie_id: int, movie_title: string, poster_url: string|null, click_count: int}>
     */
    private function getPopularMovies(): Collection
    {
        /** @var Collection<int, MovieClick> $results */
        $results = MovieClick::query()
            ->selectRaw('tmdb_movie_id, movie_title, MAX(poster_path) as poster_path, COUNT(*) as click_count')
            ->groupBy('tmdb_movie_id', 'movie_title')
            ->orderByDesc('click_count')
            ->limit(20)
            ->get();

        return $results->map(fn (MovieClick $click): array => [
            'tmdb_movie_id' => $click->tmdb_movie_id,
            'movie_title' => $click->movie_title,
            'poster_url' => TmdbService::posterUrl($click->poster_path),
            /** @phpstan-ignore-next-line Property exists via selectRaw */
            'click_count' => (int) $click->click_count,
        ]);
    }

    /**
     * Get the most recent movie views for the sidebar.
     *
     * @return Collection<int, array{id: int, tmdb_movie_id: int, movie_title: string, poster_url: ?string, clicked_at: string}>
     */
    private function getRecentViews(): Collection
    {
        return MovieClick::query()
            ->select(['id', 'tmdb_movie_id', 'movie_title', 'poster_path', 'clicked_at'])
            ->orderByDesc('clicked_at')
            ->limit(10)
            ->get()
            ->map(fn (MovieClick $click): array => [
                'id' => $click->id,
                'tmdb_movie_id' => $click->tmdb_movie_id,
                'movie_title' => $click->movie_title,
                'poster_url' => TmdbService::posterUrl($click->poster_path, 'w185'),
                'clicked_at' => $click->clicked_at->diffForHumans(),
            ]);
    }
}
