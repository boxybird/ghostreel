<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogClickRequest;
use App\Models\MovieClick;
use App\Services\MovieService;
use Illuminate\Http\Request;

class MovieClicksController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    /**
     * Determine the current page context based on the request.
     */
    private function getPageContext(Request $request): string
    {
        $referer = $request->header('Referer');
        $currentPath = $request->path();
        
        // Check if we're on movie detail page
        if (str_contains($referer, '/movies/') && str_contains($currentPath, '/movies/')) {
            return 'movie-detail';
        }
        
        // Check if we're on search page (by URL pattern)
        if (str_contains($currentPath, '/search') || $request->header('HX-Trigger') === 'search') {
            return 'search';
        }
        
        // Default to heatmap/homepage
        return 'heatmap';
    }

    public function store(LogClickRequest $request)
    {
        $validated = $request->validated();

        $click = MovieClick::create([
            'ip_address' => $request->ip(),
            'tmdb_movie_id' => $validated['tmdb_movie_id'],
            'movie_title' => $validated['movie_title'],
            'poster_path' => $validated['poster_path'] ?? null,
            'clicked_at' => now(),
        ]);

        if ($request->header('HX-Request')) {
            $recentViews = $this->movieService->getRecentViews();

            // Get updated click count for this movie
            $clickCount = MovieClick::where('tmdb_movie_id', $validated['tmdb_movie_id'])
                ->where('clicked_at', '>=', now()->subDay())
                ->count();

            // Determine page context to conditionally include OOB updates
            $context = $this->getPageContext($request);
            $isMovieDetailPage = $context === 'movie-detail';

            // Badge styling logic
            $badgeClasses = match (true) {
                $clickCount > 5 => 'bg-neon-pink text-white',
                $clickCount > 2 => 'bg-neon-orange text-dark-bg',
                default => 'bg-neon-cyan text-dark-bg',
            };

            $rank = $request->input('rank');
            $rankClasses = match ((int) $rank) {
                1 => 'bg-yellow-400 text-dark-bg',
                2 => 'bg-gray-300 text-dark-bg',
                3 => 'bg-amber-600 text-white',
                default => 'bg-dark-surface text-text-primary',
            };

            // Always include badge update
            $html = view('heatmap.partials.movie-badge', [
                'movieId' => $validated['tmdb_movie_id'],
                'rank' => $rank,
                'rankClasses' => $rankClasses,
                'showClickBadge' => true,
                'clickCount' => $clickCount,
                'badgeClasses' => $badgeClasses,
                'clickBadgeLabel' => 'view',
            ])->render();

            // Always include recent views update
            $html .= view('heatmap.partials.recent-views', [
                'recentViews' => $recentViews,
            ])->render();

            // Conditionally include movie stats update for detail pages
            if ($isMovieDetailPage) {
                $html .= '<div id="movie-stats-today" hx-swap-oob="true" class="flex items-center gap-2 px-4 py-2 bg-neon-cyan/10 border border-neon-cyan/30 rounded-full">
                    <svg class="w-5 h-5 text-neon-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/>
                    </svg>
                    <span class="text-neon-cyan font-semibold">'.$clickCount.'</span>
                    <span class="text-sm text-text-muted">views today</span>
                </div>';
            }

            return response($html);
        }

        return response()->json([
            'success' => true,
            'click_id' => $click->id,
            'recent_views' => $this->movieService->getRecentViews(),
        ]);
    }
}
