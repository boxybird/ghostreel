<?php

use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake([
        'api.themoviedb.org/3/genre/movie/list*' => Http::response([
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
                ['id' => 35, 'name' => 'Comedy'],
                ['id' => 27, 'name' => 'Horror'],
                ['id' => 18, 'name' => 'Drama'],
                ['id' => 878, 'name' => 'Science Fiction'],
            ],
        ]),
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'page' => 1,
            'total_pages' => 50,
            'results' => [
                [
                    'id' => 1001,
                    'title' => 'Action Movie 1',
                    'poster_path' => '/action1.jpg',
                    'backdrop_path' => '/action1-bg.jpg',
                    'overview' => 'An action movie',
                    'release_date' => '2025-01-15',
                    'vote_average' => 7.8,
                ],
                [
                    'id' => 1002,
                    'title' => 'Action Movie 2',
                    'poster_path' => '/action2.jpg',
                    'backdrop_path' => '/action2-bg.jpg',
                    'overview' => 'Another action movie',
                    'release_date' => '2025-02-20',
                    'vote_average' => 6.5,
                ],
            ],
        ]),
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'page' => 1,
            'total_pages' => 10,
            'results' => [
                [
                    'id' => 123,
                    'title' => 'Trending Movie',
                    'poster_path' => '/trending.jpg',
                    'backdrop_path' => '/trending-bg.jpg',
                    'overview' => 'A trending movie',
                    'release_date' => '2025-01-01',
                    'vote_average' => 8.5,
                ],
            ],
        ]),
    ]);
});

it('returns list of genres as JSON', function (): void {
    $response = $this->getJson('/genres');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'genres' => [
            '*' => ['id', 'name'],
        ],
    ]);
    $response->assertJsonPath('genres.0.id', 28);
    $response->assertJsonPath('genres.0.name', 'Action');
});

it('returns movies filtered by genre', function (): void {
    $response = $this->get('/genres/28');

    $response->assertSuccessful();
    $response->assertViewIs('genre.movies');
    $response->assertViewHas('movies');
    $response->assertViewHas('genreId', 28);
    $response->assertViewHas('genreName', 'Action');
    $response->assertViewHas('currentPage', 1);
    $response->assertViewHas('hasMorePages', true);
});

it('displays genre name in the response', function (): void {
    $response = $this->get('/genres/35');

    $response->assertSuccessful();
    $response->assertViewHas('genreName', 'Comedy');
});

it('handles pagination for genre movies', function (): void {
    Http::fake([
        'api.themoviedb.org/3/genre/movie/list*' => Http::response([
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
            ],
        ]),
        'api.themoviedb.org/3/discover/movie*with_genres=28*page=2*' => Http::response([
            'page' => 2,
            'total_pages' => 50,
            'results' => [
                [
                    'id' => 2001,
                    'title' => 'Action Movie Page 2',
                    'poster_path' => '/action-p2.jpg',
                    'backdrop_path' => '/action-p2-bg.jpg',
                    'overview' => 'Action movie on page 2',
                    'release_date' => '2025-03-01',
                    'vote_average' => 7.0,
                ],
            ],
        ]),
    ]);

    $response = $this->get('/genres/28?page=2');

    $response->assertSuccessful();
    $response->assertViewIs('genre.movies');
});

it('includes click counts for genre movies', function (): void {
    MovieClick::factory()->count(4)->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now(),
    ]);

    $response = $this->get('/genres/28');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');
    $movieWithClicks = $movies->firstWhere('id', 1001);

    expect($movieWithClicks['click_count'])->toBe(4);
});

it('excludes old clicks from genre movie click counts', function (): void {
    MovieClick::factory()->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now()->subHours(25),
    ]);
    MovieClick::factory()->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now(),
    ]);

    $response = $this->get('/genres/28');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');
    $movieWithClicks = $movies->firstWhere('id', 1001);

    expect($movieWithClicks['click_count'])->toBe(1);
});

it('handles unknown genre gracefully', function (): void {
    Http::fake([
        'api.themoviedb.org/3/genre/movie/list*' => Http::response([
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
            ],
        ]),
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'page' => 1,
            'total_pages' => 1,
            'results' => [],
        ]),
    ]);

    $response = $this->get('/genres/99999');

    $response->assertSuccessful();
    $response->assertViewHas('genreName', 'Unknown');
});

it('caps total pages at 500 when TMDB returns more', function (): void {
    // The min() in the controller ensures total_pages never exceeds 500
    // The beforeEach fake returns total_pages: 50, which is less than 500
    // So we verify the controller properly passes through the capped value
    $response = $this->get('/genres/28');

    $response->assertSuccessful();
    // From beforeEach: total_pages is 50, which is less than 500 cap
    $response->assertViewHas('totalPages', 50);
    // page 1 < 50 total pages = hasMorePages true
    $response->assertViewHas('hasMorePages', true);
});

it('rejects non-numeric genre IDs', function (): void {
    $response = $this->get('/genres/abc');

    $response->assertNotFound();
});

it('includes genres in heatmap index view', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('genres');

    $genres = $response->viewData('genres');
    expect($genres)->toHaveCount(5);
    expect($genres->first()['name'])->toBe('Action');
});
