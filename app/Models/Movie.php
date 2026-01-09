<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tmdb_id
 * @property string $title
 * @property string|null $poster_path
 * @property string|null $backdrop_path
 * @property string|null $overview
 * @property Carbon|null $release_date
 * @property float $vote_average
 * @property float|null $tmdb_popularity
 * @property string|null $tagline
 * @property int|null $runtime
 * @property array<int, array{name: string, job: string, department: string}>|null $crew
 * @property array<int, int>|null $similar_tmdb_ids
 * @property Carbon|null $details_synced_at
 * @property string $source
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TrendingSnapshot> $trendingSnapshots
 * @property-read Collection<int, GenreSnapshot> $genreSnapshots
 * @property-read Collection<int, MovieCast> $castMembers
 * @property-read Collection<int, Person> $people
 * @property-read Collection<int, Genre> $genres
 */
class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tmdb_id',
        'title',
        'poster_path',
        'backdrop_path',
        'overview',
        'release_date',
        'vote_average',
        'tmdb_popularity',
        'tagline',
        'runtime',
        'crew',
        'similar_tmdb_ids',
        'details_synced_at',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
            'release_date' => 'date',
            'vote_average' => 'decimal:1',
            'tmdb_popularity' => 'decimal:3',
            'runtime' => 'integer',
            'crew' => 'array',
            'similar_tmdb_ids' => 'array',
            'details_synced_at' => 'datetime',
        ];
    }

    /**
     * Get trending snapshots for this movie.
     *
     * @return HasMany<TrendingSnapshot, $this>
     */
    public function trendingSnapshots(): HasMany
    {
        return $this->hasMany(TrendingSnapshot::class);
    }

    /**
     * Get genre snapshots for this movie.
     *
     * @return HasMany<GenreSnapshot, $this>
     */
    public function genreSnapshots(): HasMany
    {
        return $this->hasMany(GenreSnapshot::class);
    }

    /**
     * Get cast members for this movie.
     *
     * @return HasMany<MovieCast, $this>
     */
    public function castMembers(): HasMany
    {
        return $this->hasMany(MovieCast::class);
    }

    /**
     * Get people (actors) associated with this movie.
     *
     * @return BelongsToMany<Person, $this>
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'movie_cast')
            ->withPivot(['character', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Get genres associated with this movie.
     *
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    /**
     * Get similar movies based on stored TMDB IDs.
     *
     * @return Collection<int, Movie>
     */
    public function getSimilarMoviesAttribute(): Collection
    {
        if ($this->similar_tmdb_ids === null || $this->similar_tmdb_ids === []) {
            return new Collection;
        }

        return self::whereIn('tmdb_id', $this->similar_tmdb_ids)->get();
    }

    /**
     * Check if this movie has synced details.
     */
    public function hasDetails(): bool
    {
        return $this->details_synced_at !== null;
    }

    /**
     * Scope to search movies by title.
     *
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('title', 'LIKE', "%{$term}%");
    }

    /**
     * Scope to get movies from trending source.
     *
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('source', 'trending');
    }

    /**
     * Scope to get movies with a trending snapshot on a given date.
     *
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeTrendingOn(Builder $query, Carbon $date, string $listType = 'trending_day'): Builder
    {
        return $query->whereHas('trendingSnapshots', function (Builder $q) use ($date, $listType): void {
            $q->where('snapshot_date', $date->toDateString())
                ->where('list_type', $listType);
        });
    }

    /**
     * Scope to get movies with a genre snapshot on a given date.
     *
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeInGenreOn(Builder $query, int $genreId, Carbon $date): Builder
    {
        return $query->whereHas('genreSnapshots', function (Builder $q) use ($genreId, $date): void {
            $q->where('genre_id', $genreId)
                ->where('snapshot_date', $date->toDateString());
        });
    }
}
