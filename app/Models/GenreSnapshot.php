<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $movie_id
 * @property int $genre_id
 * @property int $position
 * @property int $page
 * @property Carbon $snapshot_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Movie $movie
 * @property-read Genre $genre
 */
class GenreSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'genre_id',
        'position',
        'page',
        'snapshot_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'genre_id' => 'integer',
            'position' => 'integer',
            'page' => 'integer',
            'snapshot_date' => 'date',
        ];
    }

    /**
     * Get the movie this snapshot belongs to.
     *
     * @return BelongsTo<Movie, $this>
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Get the genre this snapshot belongs to.
     *
     * @return BelongsTo<Genre, $this>
     */
    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }
}
