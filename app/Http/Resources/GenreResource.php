<?php

namespace App\Http\Resources;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Genre
 */
class GenreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->tmdb_id,
            'name' => $this->name,
        ];
    }
}
