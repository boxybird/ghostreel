<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ip_address
 * @property int $tmdb_movie_id
 * @property string $movie_title
 * @property string|null $poster_path
 * @property Carbon $clicked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MovieClick extends Model
{
    /** @use HasFactory<\Database\Factories\MovieClickFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ip_address',
        'tmdb_movie_id',
        'movie_title',
        'poster_path',
        'clicked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tmdb_movie_id' => 'integer',
            'clicked_at' => 'datetime',
        ];
    }
}
