<?php

use App\Actions\SyncCastForMovieAction;
use App\Models\Movie;
use App\Models\MovieCast;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates cast members and persons from TMDB data', function (): void {
    $movie = Movie::factory()->create(['tmdb_id' => 550]);

    $castData = [
        [
            'id' => 287,
            'name' => 'Brad Pitt',
            'character' => 'Tyler Durden',
            'profile_path' => '/brad.jpg',
            'order' => 0,
        ],
        [
            'id' => 819,
            'name' => 'Edward Norton',
            'character' => 'The Narrator',
            'profile_path' => '/edward.jpg',
            'order' => 1,
        ],
    ];

    $action = app(SyncCastForMovieAction::class);
    $action->handle($movie, $castData);

    expect(Person::count())->toBe(2)
        ->and(MovieCast::count())->toBe(2);

    $bradPitt = Person::where('tmdb_id', 287)->first();
    expect($bradPitt)->not->toBeNull()
        ->and($bradPitt->name)->toBe('Brad Pitt')
        ->and($bradPitt->profile_path)->toBe('/brad.jpg');

    $cast = MovieCast::where('movie_id', $movie->id)->where('person_id', $bradPitt->id)->first();
    expect($cast)->not->toBeNull()
        ->and($cast->character)->toBe('Tyler Durden')
        ->and($cast->order)->toBe(0);
});

it('updates existing persons', function (): void {
    $movie = Movie::factory()->create(['tmdb_id' => 550]);

    // Create existing person with old data
    Person::create([
        'tmdb_id' => 287,
        'name' => 'William Bradley Pitt',
        'profile_path' => '/old.jpg',
    ]);

    $castData = [
        [
            'id' => 287,
            'name' => 'Brad Pitt',
            'character' => 'Tyler Durden',
            'profile_path' => '/new.jpg',
            'order' => 0,
        ],
    ];

    $action = app(SyncCastForMovieAction::class);
    $action->handle($movie, $castData);

    expect(Person::count())->toBe(1);

    $person = Person::where('tmdb_id', 287)->first();
    expect($person->name)->toBe('Brad Pitt')
        ->and($person->profile_path)->toBe('/new.jpg');
});

it('clears existing cast before syncing', function (): void {
    $movie = Movie::factory()->create(['tmdb_id' => 550]);
    $person = Person::create([
        'tmdb_id' => 100,
        'name' => 'Old Actor',
    ]);

    // Create existing cast entry
    MovieCast::create([
        'movie_id' => $movie->id,
        'person_id' => $person->id,
        'character' => 'Old Character',
        'order' => 0,
    ]);

    $castData = [
        [
            'id' => 287,
            'name' => 'Brad Pitt',
            'character' => 'Tyler Durden',
            'profile_path' => '/brad.jpg',
            'order' => 0,
        ],
    ];

    $action = app(SyncCastForMovieAction::class);
    $action->handle($movie, $castData);

    // Old cast should be removed, only new cast remains
    expect(MovieCast::where('movie_id', $movie->id)->count())->toBe(1)
        ->and(MovieCast::where('character', 'Old Character')->exists())->toBeFalse()
        ->and(MovieCast::where('character', 'Tyler Durden')->exists())->toBeTrue();
});

it('handles empty cast data', function (): void {
    $movie = Movie::factory()->create(['tmdb_id' => 550]);
    $person = Person::create([
        'tmdb_id' => 100,
        'name' => 'Old Actor',
    ]);

    // Create existing cast entry
    MovieCast::create([
        'movie_id' => $movie->id,
        'person_id' => $person->id,
        'character' => 'Old Character',
        'order' => 0,
    ]);

    $action = app(SyncCastForMovieAction::class);
    $action->handle($movie, []);

    // All cast should be cleared
    expect(MovieCast::where('movie_id', $movie->id)->count())->toBe(0);
});

it('preserves cast order from TMDB', function (): void {
    $movie = Movie::factory()->create(['tmdb_id' => 550]);

    $castData = [
        [
            'id' => 1,
            'name' => 'Actor One',
            'character' => 'Lead',
            'profile_path' => null,
            'order' => 0,
        ],
        [
            'id' => 2,
            'name' => 'Actor Two',
            'character' => 'Supporting',
            'profile_path' => null,
            'order' => 1,
        ],
        [
            'id' => 3,
            'name' => 'Actor Three',
            'character' => 'Cameo',
            'profile_path' => null,
            'order' => 5,
        ],
    ];

    $action = app(SyncCastForMovieAction::class);
    $action->handle($movie, $castData);

    $cast = MovieCast::where('movie_id', $movie->id)->orderBy('order')->get();
    expect($cast[0]->order)->toBe(0)
        ->and($cast[1]->order)->toBe(1)
        ->and($cast[2]->order)->toBe(5);
});
