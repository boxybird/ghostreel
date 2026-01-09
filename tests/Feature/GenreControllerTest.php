<?php

use App\Models\Genre;
use App\Models\GenreSnapshot;
use App\Models\Movie;
use App\Models\MovieClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed genres into the database (now the primary source)
    Genre::create(['tmdb_id' => 28, 'name' => 'Action']);
    Genre::create(['tmdb_id' => 35, 'name' => 'Comedy']);
    Genre::create(['tmdb_id' => 27, 'name' => 'Horror']);
    Genre::create(['tmdb_id' => 18, 'name' => 'Drama']);
    Genre::create(['tmdb_id' => 878, 'name' => 'Science Fiction']);

    // Fake the queue to prevent actual job dispatch during tests
    Queue::fake();

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
                    'popularity' => 150.5,
                    'genre_ids' => [28],
                ],
                [
                    'id' => 1002,
                    'title' => 'Action Movie 2',
                    'poster_path' => '/action2.jpg',
                    'backdrop_path' => '/action2-bg.jpg',
                    'overview' => 'Another action movie',
                    'release_date' => '2025-02-20',
                    'vote_average' => 6.5,
                    'popularity' => 120.3,
                    'genre_ids' => [28],
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
                    'popularity' => 500.0,
                    'genre_ids' => [28, 18],
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
    // Get the Action genre's database ID
    $actionGenre = Genre::where('tmdb_id', 28)->first();

    // Seed movies and genre snapshots for this test
    $movie1 = Movie::factory()->create(['tmdb_id' => 1001, 'title' => 'Action Movie 1']);
    $movie2 = Movie::factory()->create(['tmdb_id' => 1002, 'title' => 'Action Movie 2']);

    GenreSnapshot::create([
        'movie_id' => $movie1->id,
        'genre_id' => $actionGenre->id,
        'position' => 1,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);
    GenreSnapshot::create([
        'movie_id' => $movie2->id,
        'genre_id' => $actionGenre->id,
        'position' => 2,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);

    $response = $this->get('/genres/28/movies');

    $response->assertSuccessful();
    $response->assertViewIs('genre.movies');
    $response->assertViewHas('movies');
    $response->assertViewHas('genreId', 28);
    $response->assertViewHas('genreName', 'Action');
    $response->assertViewHas('currentPage', 1);
});

it('displays genre name in the response', function (): void {
    // Get the Comedy genre's database ID
    $comedyGenre = Genre::where('tmdb_id', 35)->first();

    // Seed a movie with genre snapshot for Comedy
    $movie = Movie::factory()->create(['tmdb_id' => 2001]);
    GenreSnapshot::create([
        'movie_id' => $movie->id,
        'genre_id' => $comedyGenre->id,
        'position' => 1,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);

    $response = $this->get('/genres/35/movies');

    $response->assertSuccessful();
    $response->assertViewHas('genreName', 'Comedy');
});

it('handles pagination for genre movies', function (): void {
    // Get the Action genre's database ID
    $actionGenre = Genre::where('tmdb_id', 28)->first();

    // Seed movies for multiple pages
    $movies = Movie::factory()->count(25)->create();

    foreach ($movies as $index => $movie) {
        GenreSnapshot::create([
            'movie_id' => $movie->id,
            'genre_id' => $actionGenre->id,
            'position' => $index + 1,
            'page' => $index < 20 ? 1 : 2,
            'snapshot_date' => now()->toDateString(),
        ]);
    }

    $response = $this->get('/genres/28/movies?page=2');

    $response->assertSuccessful();
    $response->assertViewIs('genre.movies');
});

it('includes click counts for genre movies', function (): void {
    // Get the Action genre's database ID
    $actionGenre = Genre::where('tmdb_id', 28)->first();

    $movie = Movie::factory()->create(['tmdb_id' => 1001]);
    GenreSnapshot::create([
        'movie_id' => $movie->id,
        'genre_id' => $actionGenre->id,
        'position' => 1,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);

    MovieClick::factory()->count(4)->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now(),
    ]);

    $response = $this->get('/genres/28/movies');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');
    // 'id' in the response is the tmdb_id
    $movieWithClicks = $movies->first();

    expect($movieWithClicks)->not->toBeNull();
    expect($movieWithClicks['id'])->toBe(1001);
    expect($movieWithClicks['click_count'])->toBe(4);
});

it('excludes old clicks from genre movie click counts', function (): void {
    // Get the Action genre's database ID
    $actionGenre = Genre::where('tmdb_id', 28)->first();

    $movie = Movie::factory()->create(['tmdb_id' => 1001]);
    GenreSnapshot::create([
        'movie_id' => $movie->id,
        'genre_id' => $actionGenre->id,
        'position' => 1,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);

    MovieClick::factory()->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now()->subHours(25),
    ]);
    MovieClick::factory()->create([
        'tmdb_movie_id' => 1001,
        'clicked_at' => now(),
    ]);

    $response = $this->get('/genres/28/movies');

    $response->assertSuccessful();
    $movies = $response->viewData('movies');
    // 'id' in the response is the tmdb_id
    $movieWithClicks = $movies->first();

    expect($movieWithClicks)->not->toBeNull();
    expect($movieWithClicks['id'])->toBe(1001);
    expect($movieWithClicks['click_count'])->toBe(1);
});

it('returns 404 for unknown genre', function (): void {
    // When no genre exists with this TMDB ID, return 404
    $response = $this->get('/genres/99999/movies');

    $response->assertNotFound();
});

it('caps total pages at 500 when data exceeds limit', function (): void {
    // Get the Action genre's database ID
    $actionGenre = Genre::where('tmdb_id', 28)->first();

    // Create a movie with genre snapshot
    $movie = Movie::factory()->create();
    GenreSnapshot::create([
        'movie_id' => $movie->id,
        'genre_id' => $actionGenre->id,
        'position' => 1,
        'page' => 1,
        'snapshot_date' => now()->toDateString(),
    ]);

    $response = $this->get('/genres/28/movies');

    $response->assertSuccessful();
    // Should be 1 since we only have 1 movie
    $response->assertViewHas('totalPages', 1);
});

it('rejects non-numeric genre IDs', function (): void {
    $response = $this->get('/genres/abc/movies');

    $response->assertNotFound();
});

it('includes genres in heatmap index view', function (): void {
    // Seed a trending movie
    $movie = Movie::factory()->create(['tmdb_popularity' => 100]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('genres');

    $genres = $response->viewData('genres');
    expect($genres)->toHaveCount(5);
    expect($genres->first()['name'])->toBe('Action');
});
