<?php

use App\Services\TmdbService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

it('fetches trending movies from TMDB API', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day' => Http::response([
            'page' => 1,
            'results' => [
                [
                    'id' => 123,
                    'title' => 'Test Movie',
                    'poster_path' => '/test.jpg',
                    'backdrop_path' => '/backdrop.jpg',
                    'overview' => 'A test movie overview',
                    'release_date' => '2025-01-01',
                    'vote_average' => 8.5,
                ],
            ],
        ]),
    ]);

    $service = new TmdbService('fake-token');
    $movies = $service->getTrendingMovies();

    expect($movies)->toHaveCount(1);
    expect($movies->first())->toBe([
        'id' => 123,
        'title' => 'Test Movie',
        'poster_path' => '/test.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'overview' => 'A test movie overview',
        'release_date' => '2025-01-01',
        'vote_average' => 8.5,
    ]);
});

it('returns empty collection when API fails', function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day' => Http::response([], 500),
    ]);

    $service = new TmdbService('fake-token');
    $movies = $service->getTrendingMovies();

    expect($movies)->toBeEmpty();
});

it('fetches movie details by ID', function (): void {
    Http::fake([
        'api.themoviedb.org/3/movie/456' => Http::response([
            'id' => 456,
            'title' => 'Specific Movie',
            'poster_path' => '/specific.jpg',
            'backdrop_path' => '/specific-bg.jpg',
            'overview' => 'Specific overview',
            'release_date' => '2025-06-15',
            'vote_average' => 7.2,
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
        'api.themoviedb.org/3/trending/movie/day' => Http::response([
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
