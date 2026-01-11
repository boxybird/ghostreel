<?php

namespace App\Http\Resources;

use App\Models\MovieClick;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MovieClick
 */
class RecentViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, tmdb_movie_id: int, movie_title: string, poster_url: ?string, clicked_at: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_movie_id' => $this->tmdb_movie_id,
            'movie_title' => $this->movie_title,
            'poster_url' => $this->poster_url,
            'clicked_at' => $this->clicked_at->diffForHumans(),
        ];
    }
}
