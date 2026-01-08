<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p';

    private const CACHE_TTL_MINUTES = 15;

    /**
     * Create a new TmdbService instance.
     */
    public function __construct(
        private readonly string $apiToken,
    ) {}

    /**
     * Get trending movies for today.
     *
     * @return Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>
     */
    public function getTrendingMovies(): Collection
    {
        $cacheKey = 'tmdb_trending_movies_day';

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function (): Collection {
            /** @var Response $response */
            $response = $this->client()->get('/trending/movie/day');

            if ($response->failed()) {
                return collect();
            }

            /** @var array<int, array<string, mixed>> $results */
            $results = $response->json('results', []);

            return collect($results)->map(fn (array $movie): array => $this->transformMovie($movie));
        });
    }

    /**
     * Get details for a specific movie.
     *
     * @return array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}|null
     */
    public function getMovieDetails(int $movieId): ?array
    {
        $cacheKey = "tmdb_movie_{$movieId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES * 4), function () use ($movieId): ?array {
            /** @var Response $response */
            $response = $this->client()->get("/movie/{$movieId}");

            if ($response->failed()) {
                return null;
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return $this->transformMovie($data);
        });
    }

    /**
     * Build full poster URL from path.
     */
    public static function posterUrl(?string $path, string $size = 'w500'): ?string
    {
        if ($path === null) {
            return null;
        }

        return self::IMAGE_BASE_URL."/{$size}{$path}";
    }

    /**
     * Build full backdrop URL from path.
     */
    public static function backdropUrl(?string $path, string $size = 'w1280'): ?string
    {
        if ($path === null) {
            return null;
        }

        return self::IMAGE_BASE_URL."/{$size}{$path}";
    }

    /**
     * Transform raw TMDB movie data to our application format.
     *
     * @param  array<string, mixed>  $movie
     * @return array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}
     */
    private function transformMovie(array $movie): array
    {
        return [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'poster_path' => $movie['poster_path'] ?? null,
            'backdrop_path' => $movie['backdrop_path'] ?? null,
            'overview' => $movie['overview'] ?? '',
            'release_date' => $movie['release_date'] ?? '',
            'vote_average' => (float) ($movie['vote_average'] ?? 0),
        ];
    }

    /**
     * Create configured HTTP client for TMDB API.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
                'Accept' => 'application/json',
            ]);
    }
}
