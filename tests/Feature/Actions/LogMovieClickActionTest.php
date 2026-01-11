<?php

use App\Actions\LogMovieClickAction;
use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a movie click record', function (): void {
    $action = app(LogMovieClickAction::class);

    $data = [
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'poster_path' => '/poster.jpg',
    ];

    $result = $action->handle('127.0.0.1', $data);

    expect($result)->toHaveKeys(['click', 'click_count'])
        ->and($result['click'])->toBeInstanceOf(MovieClick::class)
        ->and($result['click']->ip_address)->toBe('127.0.0.1')
        ->and($result['click']->tmdb_movie_id)->toBe(550)
        ->and($result['click']->movie_title)->toBe('Fight Club')
        ->and($result['click']->poster_path)->toBe('/poster.jpg')
        ->and($result['click_count'])->toBe(1);
});

it('handles null poster_path gracefully', function (): void {
    $action = app(LogMovieClickAction::class);

    $data = [
        'tmdb_movie_id' => 551,
        'movie_title' => 'No Poster Movie',
        'poster_path' => null,
    ];

    $result = $action->handle('127.0.0.1', $data);

    expect($result['click']->poster_path)->toBeNull();
});

it('returns correct click count for movie in last 24 hours', function (): void {
    // Create existing clicks for the same movie
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(2),
    ]);

    MovieClick::create([
        'ip_address' => '192.168.1.2',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subHours(12),
    ]);

    $action = app(LogMovieClickAction::class);

    $data = [
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'poster_path' => null,
    ];

    $result = $action->handle('127.0.0.1', $data);

    // 2 existing + 1 new = 3 total
    expect($result['click_count'])->toBe(3);
});

it('excludes clicks older than 24 hours from count', function (): void {
    // Create an old click (older than 24 hours)
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'clicked_at' => now()->subDays(2),
    ]);

    $action = app(LogMovieClickAction::class);

    $data = [
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'poster_path' => null,
    ];

    $result = $action->handle('127.0.0.1', $data);

    // Only the new click should be counted
    expect($result['click_count'])->toBe(1);
});

it('counts clicks per movie independently', function (): void {
    // Create clicks for a different movie
    MovieClick::create([
        'ip_address' => '192.168.1.1',
        'tmdb_movie_id' => 999,
        'movie_title' => 'Other Movie',
        'clicked_at' => now()->subHours(1),
    ]);

    $action = app(LogMovieClickAction::class);

    $data = [
        'tmdb_movie_id' => 550,
        'movie_title' => 'Fight Club',
        'poster_path' => null,
    ];

    $result = $action->handle('127.0.0.1', $data);

    // Only the new click for Fight Club should be counted
    expect($result['click_count'])->toBe(1);
});

it('can get click count for any movie', function (): void {
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
        'clicked_at' => now()->subHours(2),
    ]);

    $action = app(LogMovieClickAction::class);

    $count = $action->getClickCountToday(550);

    expect($count)->toBe(2);
});
