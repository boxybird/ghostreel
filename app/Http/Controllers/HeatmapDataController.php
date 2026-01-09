<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use Illuminate\Http\JsonResponse;

class HeatmapDataController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'heatmap' => $this->movieService->getHeatmapData(),
        ]);
    }
}
