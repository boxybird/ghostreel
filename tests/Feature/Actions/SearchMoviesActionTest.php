<?php

use App\Actions\SearchMoviesAction;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'poster_path' => '/fight-club.jpg',
                    'backdrop_path' => '/fight-club-bg.jpg',
                    'overview' => 'An insomniac office worker...',
                    'release_date' => '1999-10-15',
                    'vote_average' => 8.4,
                    'popularity' => 100.0,
                    'genre_ids' => [28, 18],
                ],
            ],
        ]),
    ]);
});

it('returns results and has_more flag', function (): void {
    $action = app(SearchMoviesAction::class);

    $result = $action->handle('fight');

    expect($result)->toHaveKeys(['results', 'has_more'])
        ->and($result['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('searches local database first', function (): void {
    Movie::factory()->create([
        'tmdb_id' => 999,
        'title' => 'Local Fighter',
        'tmdb_popularity' => 500.0,
    ]);

    // Create enough to avoid TMDB fallback
    Movie::factory()->count(5)->create([
        'title' => 'Fighter Extra',
        'tmdb_popularity' => 100.0,
    ]);

    $action = app(SearchMoviesAction::class);
    $result = $action->handle('fighter');

    // Should not call TMDB when we have enough local results
    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));

    expect($result['results']->pluck('id')->toArray())->toContain(999);
});

it('falls back to TMDB API when local results are insufficient', function (): void {
    // Only 2 local results (below default threshold of 5)
    Movie::factory()->count(2)->create(['title' => 'Local Fight']);

    $action = app(SearchMoviesAction::class);
    $action->handle('fight');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));
});

it('persists TMDB results to database with search source', function (): void {
    expect(Movie::count())->toBe(0);

    $action = app(SearchMoviesAction::class);
    $action->handle('fight');

    $movie = Movie::where('tmdb_id', 550)->first();
    expect($movie)->not->toBeNull()
        ->and($movie->source)->toBe('search');
});

it('deduplicates results between local and TMDB', function (): void {
    // Create a local movie with same TMDB ID as the fake response
    Movie::factory()->create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
    ]);

    $action = app(SearchMoviesAction::class);
    $result = $action->handle('fight');

    // Should only have one result with tmdb_id 550
    $fightClubCount = $result['results']->filter(fn ($m): bool => $m['id'] === 550)->count();
    expect($fightClubCount)->toBe(1);
});

it('includes db_id and poster_url in results', function (): void {
    Movie::factory()->count(10)->create(['title' => 'Fight Movie']);

    $action = app(SearchMoviesAction::class);
    $result = $action->handle('fight');

    $firstResult = $result['results']->first();
    expect($firstResult)->toHaveKeys(['db_id', 'poster_url'])
        ->and($firstResult['db_id'])->not->toBeNull();
});

it('limits results to 20 items', function (): void {
    Movie::factory()->count(25)->create(['title' => 'Fight Movie']);

    $action = app(SearchMoviesAction::class);
    $result = $action->handle('fight');

    expect($result['results'])->toHaveCount(20);
});

it('respects custom local threshold parameter', function (): void {
    // Create 3 local results
    Movie::factory()->count(3)->create(['title' => 'Local Fight']);

    $action = app(SearchMoviesAction::class);

    // With default threshold (5), should call TMDB
    $action->handle('fight', page: 1, localLimit: 20, localThreshold: 5);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));

    // Reset
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response(['results' => []]),
    ]);

    // With lower threshold (2), should NOT call TMDB since we have 3 local results
    $action->handle('fight', page: 1, localLimit: 20, localThreshold: 2);
    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));
});

it('indicates has_more as false when TMDB returns fewer than 20 results', function (): void {
    // The beforeEach fake returns only 1 result
    $action = app(SearchMoviesAction::class);
    $result = $action->handle('fight');

    expect($result['has_more'])->toBeFalse();
});
