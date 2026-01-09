<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tmdb_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Genre extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tmdb_id',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
        ];
    }

    /**
     * Get all genre snapshots for this genre.
     *
     * @return HasMany<GenreSnapshot, $this>
     */
    public function genreSnapshots(): HasMany
    {
        return $this->hasMany(GenreSnapshot::class, 'genre_id', 'tmdb_id');
    }
}
