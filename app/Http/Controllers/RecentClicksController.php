<?php

namespace App\Http\Controllers;

use App\Services\MovieRepository;
use Illuminate\Http\JsonResponse;

class RecentClicksController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'recent_views' => $this->movieRepo->getRecentViews(),
        ]);
    }
}
