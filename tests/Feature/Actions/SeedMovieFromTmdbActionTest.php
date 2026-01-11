<?php

use App\Actions\SeedMovieFromTmdbAction;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create some genres for testing
    Genre::create(['tmdb_id' => 28, 'name' => 'Action']);
    Genre::create(['tmdb_id' => 12, 'name' => 'Adventure']);
    Genre::create(['tmdb_id' => 878, 'name' => 'Science Fiction']);
});

it('creates a new movie from TMDB data', function (): void {
    $action = app(SeedMovieFromTmdbAction::class);

    $tmdbData = [
        'id' => 550,
        'title' => 'Fight Club',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'overview' => 'A ticking-tempered factory worker...',
        'release_date' => '1999-10-15',
        'vote_average' => 8.4,
        'popularity' => 100.5,
        'genre_ids' => [28, 12],
    ];

    $movie = $action->handle($tmdbData, 'search');

    expect($movie)->toBeInstanceOf(Movie::class)
        ->and($movie->tmdb_id)->toBe(550)
        ->and($movie->title)->toBe('Fight Club')
        ->and($movie->source)->toBe('search')
        ->and($movie->genres)->toHaveCount(2);
});

it('updates an existing movie from TMDB data', function (): void {
    // Create existing movie
    $existing = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Old Title',
        'poster_path' => '/old.jpg',
        'source' => 'search',
    ]);

    $action = app(SeedMovieFromTmdbAction::class);

    $tmdbData = [
        'id' => 550,
        'title' => 'Fight Club',
        'poster_path' => '/new.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'overview' => 'Updated overview',
        'release_date' => '1999-10-15',
        'vote_average' => 8.4,
        'popularity' => 100.5,
        'genre_ids' => [28],
    ];

    $movie = $action->handle($tmdbData, 'trending');

    expect($movie->id)->toBe($existing->id)
        ->and($movie->title)->toBe('Fight Club')
        ->and($movie->poster_path)->toBe('/new.jpg')
        ->and($movie->source)->toBe('trending');
});

it('handles empty genre_ids gracefully', function (): void {
    $action = app(SeedMovieFromTmdbAction::class);

    $tmdbData = [
        'id' => 551,
        'title' => 'No Genres Movie',
        'poster_path' => null,
        'backdrop_path' => null,
        'overview' => 'A movie without genres',
        'release_date' => null,
        'vote_average' => 0,
        'popularity' => 0,
        'genre_ids' => [],
    ];

    $movie = $action->handle($tmdbData, 'search');

    expect($movie)->toBeInstanceOf(Movie::class)
        ->and($movie->genres)->toHaveCount(0);
});

it('handles missing genre_ids key gracefully', function (): void {
    $action = app(SeedMovieFromTmdbAction::class);

    $tmdbData = [
        'id' => 552,
        'title' => 'Movie Without Genre Key',
        'poster_path' => null,
        'backdrop_path' => null,
        'overview' => 'A movie without genre_ids key',
        'release_date' => null,
        'vote_average' => 0,
        'popularity' => 0,
    ];

    $movie = $action->handle($tmdbData, 'search');

    expect($movie)->toBeInstanceOf(Movie::class);
});

it('syncs genres that exist in database', function (): void {
    $action = app(SeedMovieFromTmdbAction::class);

    $tmdbData = [
        'id' => 553,
        'title' => 'Genre Test Movie',
        'poster_path' => null,
        'backdrop_path' => null,
        'overview' => 'Testing genre sync',
        'release_date' => null,
        'vote_average' => 0,
        'popularity' => 0,
        'genre_ids' => [28, 878, 99999], // 99999 doesn't exist
    ];

    $movie = $action->handle($tmdbData, 'search');

    // Should only have 2 genres (Action and Science Fiction), not the non-existent one
    expect($movie->genres)->toHaveCount(2)
        ->and($movie->genres->pluck('tmdb_id')->toArray())->toContain(28, 878);
});
