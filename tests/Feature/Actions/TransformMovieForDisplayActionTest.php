<?php

use App\Actions\TransformMovieForDisplayAction;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transforms a single movie model into display array', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'overview' => 'A ticking-tempered factory worker...',
        'release_date' => '1999-10-15',
        'vote_average' => 8.4,
        'source' => 'trending',
    ]);

    $action = app(TransformMovieForDisplayAction::class);

    $result = $action->handle($movie, clickCount: 5);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe(550)
        ->and($result['db_id'])->toBe($movie->id)
        ->and($result['title'])->toBe('Fight Club')
        ->and($result['poster_path'])->toBe('/poster.jpg')
        ->and($result['backdrop_path'])->toBe('/backdrop.jpg')
        ->and($result['overview'])->toBe('A ticking-tempered factory worker...')
        ->and($result['release_date'])->toBe('1999-10-15')
        ->and($result['vote_average'])->toBe(8.4)
        ->and($result['poster_url'])->toBe(TmdbService::posterUrl('/poster.jpg'))
        ->and($result['click_count'])->toBe(5);
});

it('handles null values gracefully', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 551,
        'title' => 'Minimal Movie',
        'poster_path' => null,
        'backdrop_path' => null,
        'overview' => null,
        'release_date' => null,
        'vote_average' => 0,
        'source' => 'search',
    ]);

    $action = app(TransformMovieForDisplayAction::class);

    $result = $action->handle($movie);

    expect($result['poster_path'])->toBeNull()
        ->and($result['backdrop_path'])->toBeNull()
        ->and($result['overview'])->toBe('')
        ->and($result['release_date'])->toBe('')
        ->and($result['vote_average'])->toBe(0.0)
        ->and($result['poster_url'])->toBeNull()
        ->and($result['click_count'])->toBe(0);
});

it('transforms a collection of movies', function (): void {
    $movie1 = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
        'poster_path' => '/poster1.jpg',
        'source' => 'trending',
    ]);

    $movie2 = Movie::create([
        'tmdb_id' => 551,
        'title' => 'Matrix',
        'poster_path' => '/poster2.jpg',
        'source' => 'trending',
    ]);

    $action = app(TransformMovieForDisplayAction::class);

    $clickCounts = [
        550 => 10,
        551 => 3,
    ];

    $results = $action->collection(Movie::all(), $clickCounts);

    expect($results)->toHaveCount(2)
        ->and($results[0]['id'])->toBe(550)
        ->and($results[0]['click_count'])->toBe(10)
        ->and($results[1]['id'])->toBe(551)
        ->and($results[1]['click_count'])->toBe(3);
});

it('defaults click count to zero for movies not in click counts array', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 552,
        'title' => 'Unknown Clicks',
        'source' => 'trending',
    ]);

    $action = app(TransformMovieForDisplayAction::class);

    $clickCounts = [
        999 => 100, // Different movie ID
    ];

    $results = $action->collection(Movie::all(), $clickCounts);

    expect($results[0]['click_count'])->toBe(0);
});

it('accepts arrays in collection method', function (): void {
    $movie1 = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
        'source' => 'trending',
    ]);

    $movie2 = Movie::create([
        'tmdb_id' => 551,
        'title' => 'Matrix',
        'source' => 'trending',
    ]);

    $action = app(TransformMovieForDisplayAction::class);

    // Pass as array instead of collection
    $results = $action->collection([$movie1, $movie2]);

    expect($results)->toHaveCount(2)
        ->and($results[0]['title'])->toBe('Fight Club')
        ->and($results[1]['title'])->toBe('Matrix');
});
