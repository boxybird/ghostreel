@if($results->isEmpty())
    <div class="p-4 text-center text-text-muted">
        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm">No movies found for "{{ $query }}"</p>
    </div>
@else
    <ul class="divide-y divide-white/5">
        @foreach($results as $movie)
            <li>
                <button
                    type="button"
                    class="search-result-item w-full flex items-center gap-3 p-3 hover:bg-white/5 transition-colors text-left"
                    data-movie-id="{{ $movie['id'] }}"
                    data-movie-title="{{ $movie['title'] }}"
                    data-poster-path="{{ $movie['poster_path'] }}"
                    data-poster-url="{{ $movie['poster_url'] }}"
                    data-vote-average="{{ $movie['vote_average'] }}"
                    onclick="addMovieToGrid(this)"
                >
                    @if($movie['poster_url'])
                        <img
                            src="{{ $movie['poster_url'] }}"
                            alt="{{ $movie['title'] }}"
                            class="w-10 h-15 rounded object-cover shrink-0"
                            loading="lazy"
                        >
                    @else
                        <div class="w-10 h-15 rounded bg-dark-card flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $movie['title'] }}</p>
                        <div class="flex items-center gap-2 text-xs text-text-muted">
                            @if($movie['release_date'])
                                <span>{{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }}</span>
                            @endif
                            @if($movie['vote_average'] > 0)
                                <span class="flex items-center gap-0.5">
                                    <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    {{ number_format($movie['vote_average'], 1) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-neon-cyan shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </li>
        @endforeach
    </ul>

    @if($hasMore)
        <div
            hx-get="{{ route('search') }}?q={{ urlencode($query) }}&page={{ $page + 1 }}"
            hx-trigger="revealed"
            hx-swap="afterend"
            class="p-3 text-center"
        >
            <span class="text-xs text-text-muted">Loading more...</span>
        </div>
    @endif
@endif
