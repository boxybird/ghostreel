<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $movie_id
 * @property int $person_id
 * @property string|null $character
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Movie $movie
 * @property-read Person $person
 */
class MovieCast extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'movie_cast';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'person_id',
        'character',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'person_id' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Get the movie this cast entry belongs to.
     *
     * @return BelongsTo<Movie, $this>
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * Get the person (actor) for this cast entry.
     *
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
