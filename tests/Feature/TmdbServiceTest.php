<?php

use App\Services\TmdbService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

it('fetches trending movies from TMDB API', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'page' => 1,
            'total_pages' => 500,
            'results' => [
                [
                    'id' => 123,
                    'title' => 'Test Movie',
                    'poster_path' => '/test.jpg',
                    'backdrop_path' => '/backdrop.jpg',
                    'overview' => 'A test movie overview',
                    'release_date' => '2025-01-01',
                    'vote_average' => 8.5,
                    'popularity' => 150.5,
                    'genre_ids' => [28, 12],
                ],
            ],
        ]),
    ]);

    $service = new TmdbService('fake-token');
    $result = $service->getTrendingMovies();

    expect($result)->toHaveKeys(['movies', 'page', 'total_pages']);
    expect($result['movies'])->toHaveCount(1);
    expect($result['page'])->toBe(1);
    expect($result['total_pages'])->toBe(500);
    expect($result['movies']->first())->toBe([
        'id' => 123,
        'title' => 'Test Movie',
        'poster_path' => '/test.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'overview' => 'A test movie overview',
        'release_date' => '2025-01-01',
        'vote_average' => 8.5,
        'popularity' => 150.5,
        'genre_ids' => [28, 12],
    ]);
});

it('returns empty collection when API fails', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([], 500),
    ]);

    $service = new TmdbService('fake-token');
    $result = $service->getTrendingMovies();

    expect($result['movies'])->toBeEmpty();
    expect($result['page'])->toBe(1);
    expect($result['total_pages'])->toBe(1);
});

it('fetches movie details by ID with credits and similar movies', function (): void {
    Http::fake([
        'api.themoviedb.org/3/movie/456*' => Http::response([
            'id' => 456,
            'title' => 'Specific Movie',
            'poster_path' => '/specific.jpg',
            'backdrop_path' => '/specific-bg.jpg',
            'overview' => 'Specific overview',
            'release_date' => '2025-06-15',
            'vote_average' => 7.2,
            'popularity' => 85.3,
            'runtime' => 120,
            'tagline' => 'A great tagline',
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
                ['id' => 12, 'name' => 'Adventure'],
            ],
            'credits' => [
                'cast' => [
                    ['id' => 1, 'name' => 'Actor One', 'character' => 'Hero', 'profile_path' => '/actor1.jpg', 'order' => 0],
                    ['id' => 2, 'name' => 'Actor Two', 'character' => 'Villain', 'profile_path' => null, 'order' => 1],
                ],
                'crew' => [],
            ],
            'similar' => [
                'results' => [
                    ['id' => 789, 'title' => 'Similar Movie', 'poster_path' => '/similar.jpg', 'backdrop_path' => null, 'overview' => 'Similar overview', 'release_date' => '2024-01-01', 'vote_average' => 6.5, 'popularity' => 45.2, 'genre_ids' => [28]],
                ],
            ],
        ]),
    ]);

    $service = new TmdbService('fake-token');
    $movie = $service->getMovieDetails(456);

    expect($movie)->toBe([
        'id' => 456,
        'title' => 'Specific Movie',
        'poster_path' => '/specific.jpg',
        'backdrop_path' => '/specific-bg.jpg',
        'overview' => 'Specific overview',
        'release_date' => '2025-06-15',
        'vote_average' => 7.2,
        'popularity' => 85.3,
        'runtime' => 120,
        'tagline' => 'A great tagline',
        'genres' => [
            ['id' => 28, 'name' => 'Action'],
            ['id' => 12, 'name' => 'Adventure'],
        ],
        'cast' => [
            ['id' => 1, 'name' => 'Actor One', 'character' => 'Hero', 'profile_path' => '/actor1.jpg', 'order' => 0],
            ['id' => 2, 'name' => 'Actor Two', 'character' => 'Villain', 'profile_path' => null, 'order' => 1],
        ],
        'crew' => [],
        'similar' => [
            ['id' => 789, 'title' => 'Similar Movie', 'poster_path' => '/similar.jpg', 'backdrop_path' => null, 'overview' => 'Similar overview', 'release_date' => '2024-01-01', 'vote_average' => 6.5, 'popularity' => 45.2, 'genre_ids' => [28]],
        ],
    ]);
});

it('returns null when movie details API fails', function (): void {
    Http::fake([
        'api.themoviedb.org/3/movie/999' => Http::response([], 404),
    ]);

    $service = new TmdbService('fake-token');
    $movie = $service->getMovieDetails(999);

    expect($movie)->toBeNull();
});

it('caches trending movies response', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'page' => 1,
            'total_pages' => 1,
            'results' => [['id' => 1, 'title' => 'Cached', 'vote_average' => 5]],
        ]),
    ]);

    $service = new TmdbService('fake-token');

    // First call
    $service->getTrendingMovies();
    // Second call should use cache
    $service->getTrendingMovies();

    Http::assertSentCount(1);
});

it('supports pagination for trending movies', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => function ($request) {
            $page = $request->data()['page'] ?? 1;

            return Http::response([
                'page' => $page,
                'total_pages' => 10,
                'results' => [
                    ['id' => $page * 100, 'title' => "Movie Page {$page}", 'vote_average' => 7.0],
                ],
            ]);
        },
    ]);

    $service = new TmdbService('fake-token');

    $page1 = $service->getTrendingMovies(page: 1);
    expect($page1['page'])->toBe(1);
    expect($page1['movies']->first()['id'])->toBe(100);

    Cache::flush(); // Clear cache to test page 2

    $page2 = $service->getTrendingMovies(page: 2);
    expect($page2['page'])->toBe(2);
    expect($page2['movies']->first()['id'])->toBe(200);
});

it('builds poster URL correctly', function (): void {
    expect(TmdbService::posterUrl('/abc.jpg'))->toBe('https://image.tmdb.org/t/p/w500/abc.jpg');
    expect(TmdbService::posterUrl('/abc.jpg', 'w342'))->toBe('https://image.tmdb.org/t/p/w342/abc.jpg');
    expect(TmdbService::posterUrl(null))->toBeNull();
});

it('builds backdrop URL correctly', function (): void {
    expect(TmdbService::backdropUrl('/bg.jpg'))->toBe('https://image.tmdb.org/t/p/w1280/bg.jpg');
    expect(TmdbService::backdropUrl('/bg.jpg', 'original'))->toBe('https://image.tmdb.org/t/p/original/bg.jpg');
    expect(TmdbService::backdropUrl(null))->toBeNull();
});
