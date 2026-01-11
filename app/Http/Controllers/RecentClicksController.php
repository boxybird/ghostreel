<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecentViewResource;
use App\Services\MovieService;
use Illuminate\Http\JsonResponse;

class RecentClicksController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(): JsonResponse
    {
        $recentViews = $this->movieService->getRecentViews();

        return response()->json([
            'recent_views' => RecentViewResource::collection($recentViews),
        ]);
    }
}
