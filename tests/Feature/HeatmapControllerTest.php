<?php

use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day' => Http::response([
            'results' => [
                [
                    'id' => 123,
                    'title' => 'Test Movie',
                    'poster_path' => '/test.jpg',
                    'backdrop_path' => '/backdrop.jpg',
                    'overview' => 'A test movie',
                    'release_date' => '2025-01-01',
                    'vote_average' => 8.5,
                ],
                [
                    'id' => 456,
                    'title' => 'Another Movie',
                    'poster_path' => '/another.jpg',
                    'backdrop_path' => '/another-bg.jpg',
                    'overview' => 'Another test movie',
                    'release_date' => '2025-02-01',
                    'vote_average' => 7.2,
                ],
            ],
        ]),
    ]);
});

it('displays the heatmap index page with trending movies', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewIs('heatmap.index');
    $response->assertViewHas('movies');
    $response->assertViewHas('recentViews');
});

it('logs a movie click and returns recent views', function (): void {
    $response = $this->postJson('/click', [
        'tmdb_movie_id' => 123,
        'movie_title' => 'Test Movie',
        'poster_path' => '/test.jpg',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'click_id',
        'recent_views',
    ]);

    $this->assertDatabaseHas('movie_clicks', [
        'tmdb_movie_id' => 123,
        'movie_title' => 'Test Movie',
        'poster_path' => '/test.jpg',
    ]);
});

it('stores the visitor IP address when logging a click', function (): void {
    $this->postJson('/click', [
        'tmdb_movie_id' => 789,
        'movie_title' => 'IP Test Movie',
    ]);

    $click = MovieClick::where('tmdb_movie_id', 789)->first();

    expect($click)->not->toBeNull();
    expect($click->ip_address)->not->toBeEmpty();
});

it('validates required fields when logging a click', function (): void {
    $response = $this->postJson('/click', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['tmdb_movie_id', 'movie_title']);
});

it('allows null poster_path when logging a click', function (): void {
    $response = $this->postJson('/click', [
        'tmdb_movie_id' => 999,
        'movie_title' => 'No Poster Movie',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('movie_clicks', [
        'tmdb_movie_id' => 999,
        'poster_path' => null,
    ]);
});

it('returns recent views via API endpoint', function (): void {
    MovieClick::factory()->count(3)->create();

    $response = $this->getJson('/recent-views');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'recent_views' => [
            '*' => ['id', 'tmdb_movie_id', 'movie_title', 'poster_url', 'clicked_at'],
        ],
    ]);
});

it('limits recent views to 10 items', function (): void {
    MovieClick::factory()->count(15)->create();

    $response = $this->getJson('/recent-views');

    $response->assertSuccessful();
    expect($response->json('recent_views'))->toHaveCount(10);
});

it('returns heatmap aggregation data', function (): void {
    MovieClick::factory()->create(['tmdb_movie_id' => 100, 'clicked_at' => now()]);
    MovieClick::factory()->create(['tmdb_movie_id' => 100, 'clicked_at' => now()]);
    MovieClick::factory()->create(['tmdb_movie_id' => 200, 'clicked_at' => now()]);

    $response = $this->getJson('/heatmap-data');

    $response->assertSuccessful();
    $response->assertJsonPath('heatmap.100', 2);
    $response->assertJsonPath('heatmap.200', 1);
});

it('excludes clicks older than 24 hours from heatmap data', function (): void {
    MovieClick::factory()->create([
        'tmdb_movie_id' => 300,
        'clicked_at' => now()->subHours(25),
    ]);
    MovieClick::factory()->create([
        'tmdb_movie_id' => 400,
        'clicked_at' => now(),
    ]);

    $response = $this->getJson('/heatmap-data');

    $response->assertSuccessful();
    expect($response->json('heatmap'))->not->toHaveKey('300');
    expect($response->json('heatmap.400'))->toBe(1);
});

it('includes click counts in movie data on index page', function (): void {
    MovieClick::factory()->count(3)->create([
        'tmdb_movie_id' => 123,
        'clicked_at' => now(),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');
    $testMovie = $movies->firstWhere('id', 123);

    expect($testMovie['click_count'])->toBe(3);
});
