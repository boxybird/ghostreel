<?php

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
                ],
                [
                    'id' => 551,
                    'title' => 'Fight Night',
                    'poster_path' => '/fight-night.jpg',
                    'backdrop_path' => '/fight-night-bg.jpg',
                    'overview' => 'A boxing drama...',
                    'release_date' => '2005-03-01',
                    'vote_average' => 6.2,
                ],
            ],
        ]),
    ]);
});

it('returns search results for valid query', function (): void {
    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();
    $response->assertViewIs('heatmap.partials.search-results');
    $response->assertViewHas('results');
    $response->assertViewHas('query', 'fight');
});

it('requires a search query parameter', function (): void {
    $response = $this->getJson('/search');

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['q']);
});

it('requires minimum 2 characters for search query', function (): void {
    $response = $this->getJson('/search?q=a');

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['q']);
});

it('searches local database first', function (): void {
    // Create local movies
    Movie::factory()->create([
        'tmdb_id' => 999,
        'title' => 'Local Fighter Movie',
        'vote_average' => 9.0,
    ]);

    $response = $this->get('/search?q=fighter');

    $response->assertSuccessful();
    $results = $response->viewData('results');

    expect($results->pluck('id')->toArray())->toContain(999);
});

it('falls back to TMDB API when local results are insufficient', function (): void {
    // Only 2 local results (less than 5 threshold)
    Movie::factory()->count(2)->create([
        'title' => 'Local Fight Movie',
    ]);

    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();

    // Should have called TMDB API (faked above)
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));
});

it('does not call TMDB API when local results are sufficient', function (): void {
    // Create 10 local movies matching the search
    Movie::factory()->count(10)->create([
        'title' => 'Local Fight Movie',
    ]);

    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();

    // Should NOT have called TMDB API
    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'search/movie'));
});

it('persists TMDB search results to local database', function (): void {
    // No local movies
    expect(Movie::count())->toBe(0);

    $this->get('/search?q=fight');

    // Movies from TMDB should now be in database
    expect(Movie::where('tmdb_id', 550)->exists())->toBeTrue();
    expect(Movie::where('tmdb_id', 551)->exists())->toBeTrue();
});

it('deduplicates results between local and TMDB', function (): void {
    // Create a local movie that also exists in TMDB fake response
    Movie::factory()->create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
    ]);

    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();
    $results = $response->viewData('results');

    // Should only have one result with tmdb_id 550
    $fightClubCount = $results->filter(fn ($m): bool => $m['id'] === 550)->count();
    expect($fightClubCount)->toBe(1);
});

it('shows results from TMDB when local results are insufficient', function (): void {
    // No local movies matching the query
    // TMDB fake (from beforeEach) will return 2 "Fight" movies for any query

    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();
    $results = $response->viewData('results');

    // Should have the 2 movies from TMDB fake
    expect($results->count())->toBeGreaterThanOrEqual(2);
    expect($results->pluck('title')->toArray())->toContain('Fight Club');
});

it('includes poster URLs in search results', function (): void {
    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();
    $results = $response->viewData('results');

    expect($results->first()['poster_url'])->toContain('image.tmdb.org');
});

it('supports pagination via page parameter', function (): void {
    $response = $this->get('/search?q=fight&page=2');

    $response->assertSuccessful();
    $response->assertViewHas('page', 2);
});

it('limits results to 20 items', function (): void {
    // Create 25 local movies
    Movie::factory()->count(25)->create([
        'title' => 'Fight Movie',
    ]);

    $response = $this->get('/search?q=fight');

    $response->assertSuccessful();
    $results = $response->viewData('results');

    expect($results)->toHaveCount(20);
});

it('orders local results by vote average descending', function (): void {
    Movie::factory()->create(['title' => 'Fight Low', 'vote_average' => 5.0, 'tmdb_id' => 1]);
    Movie::factory()->create(['title' => 'Fight High', 'vote_average' => 9.0, 'tmdb_id' => 2]);
    Movie::factory()->create(['title' => 'Fight Mid', 'vote_average' => 7.0, 'tmdb_id' => 3]);

    // Add more to reach threshold and avoid TMDB call
    Movie::factory()->count(5)->create(['title' => 'Fight Extra', 'vote_average' => 6.0]);

    $response = $this->get('/search?q=fight');

    $results = $response->viewData('results');
    $titles = $results->pluck('title')->take(3)->toArray();

    expect($titles[0])->toBe('Fight High');
});

it('sets source to search when persisting TMDB results', function (): void {
    $this->get('/search?q=fight');

    $movie = Movie::where('tmdb_id', 550)->first();

    expect($movie->source)->toBe('search');
});
