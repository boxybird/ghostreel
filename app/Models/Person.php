<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tmdb_id
 * @property string $name
 * @property string|null $profile_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Person extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tmdb_id',
        'name',
        'profile_path',
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
     * Get all movie cast entries for this person.
     *
     * @return HasMany<MovieCast, $this>
     */
    public function movieCastEntries(): HasMany
    {
        return $this->hasMany(MovieCast::class);
    }
}
