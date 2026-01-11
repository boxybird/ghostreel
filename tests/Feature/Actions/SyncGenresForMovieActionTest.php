<?php

use App\Actions\SyncGenresForMovieAction;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Genre::create(['tmdb_id' => 28, 'name' => 'Action']);
    Genre::create(['tmdb_id' => 12, 'name' => 'Adventure']);
    Genre::create(['tmdb_id' => 878, 'name' => 'Science Fiction']);
});

it('syncs genres to a movie', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Test Movie',
        'source' => 'search',
    ]);

    $action = new SyncGenresForMovieAction;
    $action->handle($movie, [28, 12]);

    expect($movie->genres)->toHaveCount(2)
        ->and($movie->genres->pluck('tmdb_id')->toArray())->toContain(28, 12);
});

it('replaces existing genres on re-sync', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Test Movie',
        'source' => 'search',
    ]);

    $action = new SyncGenresForMovieAction;

    // First sync
    $action->handle($movie, [28, 12]);
    expect($movie->fresh()->genres)->toHaveCount(2);

    // Second sync with different genres
    $action->handle($movie, [878]);
    expect($movie->fresh()->genres)->toHaveCount(1)
        ->and($movie->fresh()->genres->first()->tmdb_id)->toBe(878);
});

it('handles empty genre array', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Test Movie',
        'source' => 'search',
    ]);

    // First add some genres
    $movie->genres()->attach(Genre::where('tmdb_id', 28)->first()->id);

    $action = new SyncGenresForMovieAction;
    $action->handle($movie, []);

    // Empty array should not change anything (early return)
    expect($movie->fresh()->genres)->toHaveCount(1);
});

it('ignores non-existent genre IDs', function (): void {
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Test Movie',
        'source' => 'search',
    ]);

    $action = new SyncGenresForMovieAction;
    $action->handle($movie, [28, 99999, 88888]); // 99999 and 88888 don't exist

    expect($movie->genres)->toHaveCount(1)
        ->and($movie->genres->first()->tmdb_id)->toBe(28);
});
