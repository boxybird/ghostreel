<?php

namespace App\Http\Controllers;

use App\Services\MovieRepository;
use Illuminate\Http\JsonResponse;

class GenresController extends Controller
{
    public function __construct(
        private readonly MovieRepository $movieRepo,
    ) {}

    public function index(): JsonResponse
    {
        $genres = $this->movieRepo->getAllGenres();

        return response()->json([
            'genres' => $genres->map(fn ($g): array => ['id' => $g->tmdb_id, 'name' => $g->name]),
        ]);
    }
}
