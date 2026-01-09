<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogClickRequest;
use App\Models\MovieClick;
use App\Services\MovieRepository;
use Illuminate\Http\JsonResponse;

class MovieClicksController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    public function store(LogClickRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $click = MovieClick::create([
            'ip_address' => $request->ip(),
            'tmdb_movie_id' => $validated['tmdb_movie_id'],
            'movie_title' => $validated['movie_title'],
            'poster_path' => $validated['poster_path'] ?? null,
            'clicked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'click_id' => $click->id,
            'recent_views' => $this->movieRepo->getRecentViews(),
        ]);
    }
}
