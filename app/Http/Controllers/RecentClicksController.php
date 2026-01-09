<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use Illuminate\Http\JsonResponse;

class RecentClicksController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'recent_views' => $this->movieService->getRecentViews(),
        ]);
    }
}
