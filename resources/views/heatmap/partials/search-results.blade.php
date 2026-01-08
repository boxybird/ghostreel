@if($results->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-text-muted">
        <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-lg font-medium mb-1">No movies found</p>
        <p class="text-sm opacity-70">Try searching for "{{ $query }}" with different keywords</p>
    </div>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        @foreach($results as $movie)
            <button
                type="button"
                class="search-result-card group relative rounded-xl overflow-hidden bg-dark-card text-left transition-all duration-200 hover:scale-[1.03] hover:ring-2 hover:ring-neon-cyan/50 hover:shadow-lg hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
                data-movie-id="{{ $movie['id'] }}"
                data-movie-title="{{ $movie['title'] }}"
                data-poster-path="{{ $movie['poster_path'] }}"
                data-poster-url="{{ $movie['poster_url'] }}"
                data-vote-average="{{ $movie['vote_average'] }}"
                onclick="addMovieToGrid(this)"
            >
                <!-- Poster Image -->
                @if($movie['poster_url'])
                    <img
                        src="{{ $movie['poster_url'] }}"
                        alt="{{ $movie['title'] }}"
                        class="w-full aspect-[2/3] object-cover"
                        loading="lazy"
                    >
                @else
                    <div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
                        <svg class="w-12 h-12 text-text-muted opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                @endif

                <!-- Title Overlay (always visible) -->
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-3 pt-10">
                    <p class="text-sm font-medium line-clamp-2 leading-tight">{{ $movie['title'] }}</p>
                    <div class="flex items-center gap-2 mt-1 text-xs text-text-muted">
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

                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-dark-bg/80 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col items-center justify-center text-center p-4">
                    <p class="text-sm font-semibold mb-1 line-clamp-3">{{ $movie['title'] }}</p>
                    <div class="flex items-center gap-2 text-xs text-text-muted mb-3">
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
                    <span class="inline-flex items-center gap-1.5 text-neon-cyan text-xs font-medium bg-neon-cyan/10 px-3 py-1.5 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add to grid
                    </span>
                </div>
            </button>
        @endforeach
    </div>

    @if($hasMore)
        <div
            hx-get="{{ route('search') }}?q={{ urlencode($query) }}&page={{ $page + 1 }}"
            hx-trigger="revealed"
            hx-swap="afterend"
            class="flex justify-center py-6"
        >
            <div class="flex items-center gap-2 text-sm text-text-muted">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading more results...
            </div>
        </div>
    @endif
@endif
