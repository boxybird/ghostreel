<?php

namespace App\Http\Controllers;

use App\Actions\LogMovieClickAction;
use App\Http\Requests\LogClickRequest;
use App\Http\Resources\RecentViewResource;
use App\Services\MovieService;
use Illuminate\Http\Request;

class MovieClickController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
        private readonly LogMovieClickAction $logMovieClick,
    ) {}

    /**
     * Determine current page context based on referer.
     */
    private function getPageContext(Request $request): string
    {
        $referer = $request->header('Referer');

        if ($referer && str_contains($referer, '/movies/')) {
            return 'movie-detail';
        }

        if ($referer && str_contains($referer, '/search')) {
            return 'search';
        }

        return 'heatmap';
    }

    public function store(LogClickRequest $request)
    {
        $validated = $request->validated();

        $result = $this->logMovieClick->handle((string) $request->ip(), $validated);
        $click = $result['click'];
        $clickCount = $result['click_count'];

        if ($request->header('HX-Request')) {

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

            // 1. Always include badge update (this is the direct target for movie cards)
            $html = view('heatmap.partials.movie-badge', [
                'movieId' => $validated['tmdb_movie_id'],
                'rank' => $rank,
                'rankClasses' => $rankClasses,
                'showClickBadge' => true,
                'clickCount' => $clickCount,
                'badgeClasses' => $badgeClasses,
                'clickBadgeLabel' => 'view',
            ])->render();

            // 2. Conditionally include sidebar update (only if not on detail page)
            if (! $isMovieDetailPage) {
                $recentViews = $this->movieService->getRecentViews();
                $html .= view('heatmap.partials.recent-views', [
                    'recentViews' => $recentViews,
                ])->render();
            }

            // 3. Conditionally include movie stats update for detail pages
            if ($isMovieDetailPage) {
                $html .= view('heatmap.partials.movie-stats-today', [
                    'clickCount' => $clickCount,
                    'oobSwap' => true,
                ])->render();
            }

            return response($html);
        }

        return response()->json([
            'success' => true,
            'click_id' => $click->id,
            'recent_views' => RecentViewResource::collection($this->movieService->getRecentViews()),
        ]);
    }
}
