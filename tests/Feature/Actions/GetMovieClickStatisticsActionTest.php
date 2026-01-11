<?php

use App\Actions\GetMovieClickStatisticsAction;
use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns today and total click counts', function (): void {
    $tmdbId = 550;

    // Create some clicks for today (within last 24 hours)
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => $tmdbId,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(2),
    ]);

    MovieClick::create([
        'ip_address' => '192.168.1.2',
        'tmdb_movie_id' => $tmdbId,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(10),
    ]);

    // Create an old click (older than 24 hours)
    MovieClick::create([
        'ip_address' => '192.168.1.3',
        'tmdb_movie_id' => $tmdbId,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subDays(2),
    ]);

    $action = app(GetMovieClickStatisticsAction::class);
    $result = $action->handle($tmdbId);

    expect($result)->toHaveKeys(['today_count', 'total_count'])
        ->and($result['today_count'])->toBe(2)
        ->and($result['total_count'])->toBe(3);
});

it('returns zeros for movie with no clicks', function (): void {
    $action = app(GetMovieClickStatisticsAction::class);
    $result = $action->handle(999);

    expect($result['today_count'])->toBe(0)
        ->and($result['total_count'])->toBe(0);
});

it('only counts clicks for the specified movie', function (): void {
    // Clicks for movie 550
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(1),
    ]);

    // Clicks for different movie
    MovieClick::create([
        'ip_address' => '192.168.1.2',
        'tmdb_movie_id' => 999,
        'movie_title' => 'Other Movie',
        'clicked_at' => now()->subHours(1),
    ]);

    $action = app(GetMovieClickStatisticsAction::class);
    $result = $action->handle(550);

    expect($result['today_count'])->toBe(1)
        ->and($result['total_count'])->toBe(1);
});

it('can get today count independently', function (): void {
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(1),
    ]);

    MovieClick::create([
        'ip_address' => '192.168.1.2',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subDays(2),
    ]);

    $action = app(GetMovieClickStatisticsAction::class);

    expect($action->getTodayCount(550))->toBe(1);
});

it('can get total count independently', function (): void {
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(1),
    ]);

    MovieClick::create([
        'ip_address' => '192.168.1.2',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subDays(2),
    ]);

    $action = app(GetMovieClickStatisticsAction::class);

    expect($action->getTotalCount(550))->toBe(2);
});
