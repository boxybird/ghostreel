<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use Illuminate\Http\JsonResponse;

class GenresController extends Controller
{
    public function __construct(
        private readonly MovieService $movieService,
    ) {}

    public function index(): JsonResponse
    {
        $genres = $this->movieService->getAllGenres();

        return response()->json([
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
        ]);
    }
}
