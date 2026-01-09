<?php

use App\Models\Genre;
use App\Models\Movie;
use App\Models\MovieClick;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('MovieController', function (): void {
    describe('show', function (): void {
        it('displays movie detail page for existing movie', function (): void {
            Queue::fake();

            // Create genres in DB for the controller to look up
            $actionGenre = Genre::factory()->create(['tmdb_id' => 28, 'name' => 'Action']);
            $adventureGenre = Genre::factory()->create(['tmdb_id' => 12, 'name' => 'Adventure']);

            $movie = Movie::factory()->create([
                'title' => 'Test Movie Title',
                'overview' => 'A great movie about testing.',
                'vote_average' => 8.5,
                'tagline' => 'The ultimate test',
                'runtime' => 120,
                'details_synced_at' => now(), // Mark as already synced so no job dispatches
            ]);

            // Attach genres via the pivot table relationship
            $movie->genres()->attach([$actionGenre->id, $adventureGenre->id]);

            // Mock TMDB service for cast retrieval fallback (since no cast in DB)
            $this->mock(TmdbService::class, function ($mock) use ($movie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->with($movie->tmdb_id)
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => $movie->poster_path,
                        'backdrop_path' => $movie->backdrop_path,
                        'overview' => $movie->overview,
                        'release_date' => '2024-01-15',
                        'vote_average' => 8.5,
                        'popularity' => 100.0,
                        'runtime' => 120,
                        'tagline' => 'The ultimate test',
                        'genres' => [
                            ['id' => 28, 'name' => 'Action'],
                            ['id' => 12, 'name' => 'Adventure'],
                        ],
                        'cast' => [
                            [
                                'id' => 1,
                                'name' => 'Test Actor',
                                'character' => 'Hero',
                                'profile_path' => '/actor.jpg',
                                'order' => 0,
                            ],
                        ],
                        'crew' => [],
                        'similar' => [],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee($movie->title)
                ->assertSee('A great movie about testing.')
                ->assertSee('8.5')
                ->assertSee('Action')
                ->assertSee('Adventure');
        });

        it('returns 404 for non-existent movie', function (): void {
            $response = $this->get('/movies/99999');

            $response->assertNotFound();
        });

        it('displays community click count for last 24 hours', function (): void {
            Queue::fake();

            $movie = Movie::factory()->create([
                'details_synced_at' => now(),
            ]);

            // Create clicks within last 24 hours
            MovieClick::factory()->count(5)->create([
                'tmdb_movie_id' => $movie->tmdb_id,
                'clicked_at' => now()->subHours(2),
            ]);

            // Create older clicks (should not be counted for today)
            MovieClick::factory()->count(3)->create([
                'tmdb_movie_id' => $movie->tmdb_id,
                'clicked_at' => now()->subDays(2),
            ]);

            $this->mock(TmdbService::class, function ($mock) use ($movie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => null,
                        'backdrop_path' => null,
                        'overview' => '',
                        'release_date' => '',
                        'vote_average' => 0,
                        'popularity' => 0,
                        'runtime' => null,
                        'tagline' => null,
                        'genres' => [],
                        'cast' => [],
                        'crew' => [],
                        'similar' => [],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee('5') // Today's count
                ->assertSee('views today')
                ->assertSee('8') // All-time count (5 + 3)
                ->assertSee('all time');
        });

        it('displays cast members with profile images', function (): void {
            Queue::fake();

            // Don't set details_synced_at so it falls back to TMDB API for cast
            $movie = Movie::factory()->create();

            $this->mock(TmdbService::class, function ($mock) use ($movie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => null,
                        'backdrop_path' => null,
                        'overview' => '',
                        'release_date' => '',
                        'vote_average' => 0,
                        'popularity' => 0,
                        'runtime' => null,
                        'tagline' => null,
                        'genres' => [],
                        'cast' => [
                            [
                                'id' => 1,
                                'name' => 'Leonardo DiCaprio',
                                'character' => 'Dom Cobb',
                                'profile_path' => '/leo.jpg',
                                'order' => 0,
                            ],
                            [
                                'id' => 2,
                                'name' => 'Ellen Page',
                                'character' => 'Ariadne',
                                'profile_path' => null,
                                'order' => 1,
                            ],
                        ],
                        'crew' => [],
                        'similar' => [],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee('Leonardo DiCaprio')
                ->assertSee('Dom Cobb')
                ->assertSee('Ellen Page')
                ->assertSee('Ariadne');
        });

        it('displays similar movies with links when they exist in database', function (): void {
            Queue::fake();

            $similarMovie = Movie::factory()->create([
                'tmdb_id' => 12345,
                'title' => 'Similar Movie',
            ]);

            $movie = Movie::factory()->create([
                'details_synced_at' => now(),
            ]);

            // Attach similar movie via the relationship
            $movie->similarMovies()->attach($similarMovie);

            $this->mock(TmdbService::class, function ($mock) use ($movie, $similarMovie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => null,
                        'backdrop_path' => null,
                        'overview' => '',
                        'release_date' => '',
                        'vote_average' => 0,
                        'popularity' => 0,
                        'runtime' => null,
                        'tagline' => null,
                        'genres' => [],
                        'cast' => [],
                        'crew' => [],
                        'similar' => [
                            [
                                'id' => $similarMovie->tmdb_id,
                                'title' => 'Similar Movie',
                                'poster_path' => '/similar.jpg',
                                'backdrop_path' => null,
                                'overview' => '',
                                'release_date' => '2023-01-01',
                                'vote_average' => 7.0,
                                'popularity' => 50.0,
                                'genre_ids' => [],
                            ],
                        ],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee('Similar Movies')
                ->assertSee('Similar Movie')
                ->assertSee(route('movies.show', $similarMovie));
        });

        it('handles null TMDB response gracefully', function (): void {
            Queue::fake();

            $movie = Movie::factory()->create([
                'details_synced_at' => now(),
            ]);

            $this->mock(TmdbService::class, function ($mock): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn(null);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee($movie->title);
        });

        it('displays runtime in hours and minutes format', function (): void {
            Queue::fake();

            $movie = Movie::factory()->create([
                'runtime' => 148, // 2h 28m
                'details_synced_at' => now(),
            ]);

            $this->mock(TmdbService::class, function ($mock) use ($movie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => null,
                        'backdrop_path' => null,
                        'overview' => '',
                        'release_date' => '',
                        'vote_average' => 0,
                        'popularity' => 0,
                        'runtime' => 148, // 2h 28m
                        'tagline' => null,
                        'genres' => [],
                        'cast' => [],
                        'crew' => [],
                        'similar' => [],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee('2h 28m');
        });

        it('displays tagline when available', function (): void {
            Queue::fake();

            $movie = Movie::factory()->create([
                'tagline' => 'Your mind is the scene of the crime.',
                'details_synced_at' => now(),
            ]);

            $this->mock(TmdbService::class, function ($mock) use ($movie): void {
                $mock->shouldReceive('getMovieDetails')
                    ->andReturn([
                        'id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'poster_path' => null,
                        'backdrop_path' => null,
                        'overview' => '',
                        'release_date' => '',
                        'vote_average' => 0,
                        'popularity' => 0,
                        'runtime' => null,
                        'tagline' => 'Your mind is the scene of the crime.',
                        'genres' => [],
                        'cast' => [],
                        'crew' => [],
                        'similar' => [],
                    ]);
            });

            $response = $this->get(route('movies.show', $movie));

            $response->assertSuccessful()
                ->assertSee('Your mind is the scene of the crime.');
        });
    });
});
