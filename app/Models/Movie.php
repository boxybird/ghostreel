<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tmdb_id
 * @property string $title
 * @property string|null $poster_path
 * @property string|null $backdrop_path
 * @property string|null $overview
 * @property Carbon|null $release_date
 * @property float $vote_average
 * @property string $source
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        ];
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
}
