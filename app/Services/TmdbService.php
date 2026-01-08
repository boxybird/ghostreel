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
     * @return array{movies: Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>, page: int, total_pages: int}
     */
    public function getTrendingMovies(int $page = 1): array
    {
        $cacheKey = "tmdb_trending_movies_day_page_{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($page): array {
            /** @var Response $response */
            $response = $this->client()->get('/trending/movie/day', [
                'page' => $page,
            ]);

            if ($response->failed()) {
                return [
                    'movies' => collect(),
                    'page' => $page,
                    'total_pages' => 1,
                ];
            }

            /** @var array<int, array<string, mixed>> $results */
            $results = $response->json('results', []);

            return [
                'movies' => collect($results)->map(fn (array $movie): array => $this->transformMovie($movie)),
                'page' => (int) $response->json('page', 1),
                'total_pages' => (int) $response->json('total_pages', 1),
            ];
        });
    }

    /**
     * Search movies by query string.
     *
     * @return Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>
     */
    public function searchMovies(string $query, int $page = 1): Collection
    {
        if (trim($query) === '') {
            return collect();
        }

        $cacheKey = 'tmdb_search_'.md5($query)."_page_{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($query, $page): Collection {
            /** @var Response $response */
            $response = $this->client()->get('/search/movie', [
                'query' => $query,
                'page' => $page,
                'include_adult' => false,
            ]);

            if ($response->failed()) {
                return collect();
            }

            /** @var array<int, array<string, mixed>> $results */
            $results = $response->json('results', []);

            return collect($results)->map(fn (array $movie): array => $this->transformMovie($movie));
        });
    }

    /**
     * Get list of movie genres from TMDB.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getGenres(): Collection
    {
        $cacheKey = 'tmdb_movie_genres';

        return Cache::remember($cacheKey, now()->addHours(24), function (): Collection {
            /** @var Response $response */
            $response = $this->client()->get('/genre/movie/list');

            if ($response->failed()) {
                return collect();
            }

            /** @var array<int, array{id: int, name: string}> $genres */
            $genres = $response->json('genres', []);

            return collect($genres);
        });
    }

    /**
     * Discover movies by genre.
     *
     * @return array{movies: Collection<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>, page: int, total_pages: int}
     */
    public function discoverMoviesByGenre(int $genreId, int $page = 1): array
    {
        $cacheKey = "tmdb_discover_genre_{$genreId}_page_{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($genreId, $page): array {
            /** @var Response $response */
            $response = $this->client()->get('/discover/movie', [
                'with_genres' => $genreId,
                'sort_by' => 'popularity.desc',
                'page' => $page,
                'include_adult' => false,
            ]);

            if ($response->failed()) {
                return [
                    'movies' => collect(),
                    'page' => $page,
                    'total_pages' => 1,
                ];
            }

            /** @var array<int, array<string, mixed>> $results */
            $results = $response->json('results', []);

            return [
                'movies' => collect($results)->map(fn (array $movie): array => $this->transformMovie($movie)),
                'page' => (int) $response->json('page', 1),
                'total_pages' => (int) $response->json('total_pages', 1),
            ];
        });
    }

    /**
     * Get details for a specific movie with extended information.
     *
     * @return array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, runtime: ?int, tagline: ?string, genres: array<int, array{id: int, name: string}>, cast: array<int, array{id: int, name: string, character: string, profile_path: ?string}>, similar: array<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>}|null
     */
    public function getMovieDetails(int $movieId): ?array
    {
        $cacheKey = "tmdb_movie_details_{$movieId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES * 4), function () use ($movieId): ?array {
            /** @var Response $response */
            $response = $this->client()->get("/movie/{$movieId}", [
                'append_to_response' => 'credits,similar',
            ]);

            if ($response->failed()) {
                return null;
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return $this->transformMovieDetails($data);
        });
    }

    /**
     * Transform raw TMDB movie details response to our extended application format.
     *
     * @param  array<string, mixed>  $movie
     * @return array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float, runtime: ?int, tagline: ?string, genres: array<int, array{id: int, name: string}>, cast: array<int, array{id: int, name: string, character: string, profile_path: ?string}>, similar: array<int, array{id: int, title: string, poster_path: ?string, backdrop_path: ?string, overview: string, release_date: string, vote_average: float}>}
     */
    private function transformMovieDetails(array $movie): array
    {
        /** @var array<int, array{id: int, name: string}> $genres */
        $genres = $movie['genres'] ?? [];

        /** @var array<string, mixed> $credits */
        $credits = $movie['credits'] ?? [];

        /** @var array<int, array<string, mixed>> $rawCast */
        $rawCast = $credits['cast'] ?? [];

        /** @var array<string, mixed> $similar */
        $similar = $movie['similar'] ?? [];

        /** @var array<int, array<string, mixed>> $rawSimilar */
        $rawSimilar = $similar['results'] ?? [];

        // Transform cast - limit to top 10
        $cast = collect($rawCast)
            ->take(10)
            ->map(fn (array $person): array => [
                'id' => $person['id'],
                'name' => $person['name'],
                'character' => $person['character'] ?? '',
                'profile_path' => $person['profile_path'] ?? null,
            ])
            ->values()
            ->all();

        // Transform similar movies - limit to 6
        $similarMovies = collect($rawSimilar)
            ->take(6)
            ->map(fn (array $m): array => $this->transformMovie($m))
            ->values()
            ->all();

        return [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'poster_path' => $movie['poster_path'] ?? null,
            'backdrop_path' => $movie['backdrop_path'] ?? null,
            'overview' => $movie['overview'] ?? '',
            'release_date' => $movie['release_date'] ?? '',
            'vote_average' => (float) ($movie['vote_average'] ?? 0),
            'runtime' => $movie['runtime'] ?? null,
            'tagline' => $movie['tagline'] ?? null,
            'genres' => $genres,
            'cast' => $cast,
            'similar' => $similarMovies,
        ];
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
     * Build full profile image URL from path.
     */
    public static function profileUrl(?string $path, string $size = 'w185'): ?string
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
