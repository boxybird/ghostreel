<?php

use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays the popular page', function (): void {
    $response = $this->get('/popular');

    $response->assertSuccessful();
    $response->assertViewIs('popular.index');
    $response->assertViewHas('movies');
});

it('shows empty state when no clicks exist', function (): void {
    $response = $this->get('/popular');

    $response->assertSuccessful();
    $response->assertSee('No popular movies yet');
    $response->assertSee('Browse Trending Movies');
});

it('displays movies ordered by click count descending', function (): void {
    // Create clicks for different movies with varying counts
    MovieClick::factory()->count(5)->create([
        'tmdb_movie_id' => 100,
        'movie_title' => 'Most Popular Movie',
    ]);
    MovieClick::factory()->count(2)->create([
        'tmdb_movie_id' => 200,
        'movie_title' => 'Less Popular Movie',
    ]);
    MovieClick::factory()->count(10)->create([
        'tmdb_movie_id' => 300,
        'movie_title' => 'Super Popular Movie',
    ]);

    $response = $this->get('/popular');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');

    expect($movies)->toHaveCount(3);
    expect($movies[0]['movie_title'])->toBe('Super Popular Movie');
    expect($movies[0]['click_count'])->toBe(10);
    expect($movies[1]['movie_title'])->toBe('Most Popular Movie');
    expect($movies[1]['click_count'])->toBe(5);
    expect($movies[2]['movie_title'])->toBe('Less Popular Movie');
    expect($movies[2]['click_count'])->toBe(2);
});

it('limits results to 20 movies', function (): void {
    // Create 25 different movies with clicks
    for ($i = 1; $i <= 25; $i++) {
        MovieClick::factory()->create([
            'tmdb_movie_id' => $i,
            'movie_title' => "Movie {$i}",
        ]);
    }

    $response = $this->get('/popular');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');

    expect($movies)->toHaveCount(20);
});

it('aggregates clicks for the same movie', function (): void {
    // Multiple clicks for same movie from different IPs
    MovieClick::factory()->create([
        'tmdb_movie_id' => 500,
        'movie_title' => 'Aggregated Movie',
        'ip_address' => '192.168.1.1',
    ]);
    MovieClick::factory()->create([
        'tmdb_movie_id' => 500,
        'movie_title' => 'Aggregated Movie',
        'ip_address' => '192.168.1.2',
    ]);
    MovieClick::factory()->create([
        'tmdb_movie_id' => 500,
        'movie_title' => 'Aggregated Movie',
        'ip_address' => '192.168.1.3',
    ]);

    $response = $this->get('/popular');

    $movies = $response->viewData('movies');

    expect($movies)->toHaveCount(1);
    expect($movies[0]['click_count'])->toBe(3);
});

it('includes poster url in movie data', function (): void {
    MovieClick::factory()->create([
        'tmdb_movie_id' => 600,
        'movie_title' => 'Movie With Poster',
        'poster_path' => '/poster123.jpg',
    ]);

    $response = $this->get('/popular');

    $movies = $response->viewData('movies');

    expect($movies[0]['poster_url'])->toContain('image.tmdb.org');
    expect($movies[0]['poster_url'])->toContain('poster123.jpg');
});

it('handles null poster path gracefully', function (): void {
    MovieClick::factory()->create([
        'tmdb_movie_id' => 700,
        'movie_title' => 'Movie Without Poster',
        'poster_path' => null,
    ]);

    $response = $this->get('/popular');

    $movies = $response->viewData('movies');

    expect($movies[0]['poster_url'])->toBeNull();
});

it('has working navigation link to home', function (): void {
    $response = $this->get('/popular');

    $response->assertSuccessful();
    $response->assertSee(route('heatmap.index'));
});
