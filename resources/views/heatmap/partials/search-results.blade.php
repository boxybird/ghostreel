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
            <x-movie-card
                :movie="$movie"
                variant="search"
                :show-ghost-action="true"
                :show-rating="true"
            />
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
