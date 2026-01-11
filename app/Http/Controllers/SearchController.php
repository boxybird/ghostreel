<?php

namespace App\Http\Controllers;

use App\Actions\SearchMoviesAction;
use App\Http\Requests\SearchRequest;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchMoviesAction $searchMovies,
    ) {}

    /**
     * Search movies: local-first with TMDB API fallback.
     * Returns Blade fragment for HTMX.
     */
    public function index(SearchRequest $request): View
    {
        $query = $request->validated('q');
        $page = $request->validated('page', 1);

        $result = $this->searchMovies->handle($query, $page);

        return view('heatmap.partials.search-results', [
            'results' => $result['results'],
            'query' => $query,
            'page' => $page,
            'hasMore' => $result['has_more'],
        ]);
    }
}
