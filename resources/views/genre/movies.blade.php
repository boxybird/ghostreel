{{-- Genre movies partial for HTMX genre filtering --}}

{{-- OOB Swap: Update genre chips to show active state --}}
<div id="genre-chips" hx-swap-oob="true" class="mt-4 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <button
        type="button"
        hx-get="{{ route('heatmap.trending') }}"
        hx-target="#movie-grid"
        hx-swap="innerHTML"
        class="genre-chip shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
        data-genre-id="all"
    >
        All
    </button>
    @foreach($genres as $genre)
        <button
            type="button"
            hx-get="{{ route('genres.show', $genre['id']) }}"
            hx-target="#movie-grid"
            hx-swap="innerHTML"
            class="genre-chip {{ $genre['id'] == $genreId ? 'genre-chip-active' : '' }} shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
            data-genre-id="{{ $genre['id'] }}"
        >
            {{ $genre['name'] }}
        </button>
    @endforeach
</div>

{{-- Genre Header - Full width above grid --}}
<div class="col-span-full mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-text-primary">{{ $genreName }} Movies</h2>
        <p class="text-sm text-text-muted">Browse popular {{ strtolower($genreName) }} movies</p>
    </div>
    <button
        type="button"
        hx-get="{{ route('heatmap.trending') }}"
        hx-target="#movie-grid"
        hx-swap="innerHTML"
        class="flex items-center gap-2 px-3 py-1.5 text-sm text-text-muted hover:text-neon-cyan transition-colors"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Clear filter
    </button>
</div>

{{-- Movie Cards --}}
@foreach($movies as $movie)
    <x-movie-card
        :movie="$movie"
        :clickable="true"
        :show-eye-icon="true"
        :show-heatmap-glow="true"
        :show-click-badge="true"
        click-badge-label="view"
        :show-rating="true"
    />
@endforeach

{{-- Load More Button for Genre --}}
@if($hasMorePages)
    <div id="load-more-container" class="col-span-full flex justify-center py-6">
        <button
            type="button"
            hx-get="{{ route('genres.show', $genreId) }}?page={{ $currentPage + 1 }}"
            hx-target="#load-more-container"
            hx-swap="outerHTML"
            hx-indicator="#load-more-spinner"
            class="group flex items-center gap-2 px-6 py-3 bg-dark-surface border border-white/10 rounded-xl hover:bg-white/10 hover:border-neon-cyan/30 transition-colors"
        >
            <span class="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors">Load More {{ $genreName }} Movies</span>
            <svg id="load-more-spinner" class="htmx-indicator w-4 h-4 text-neon-cyan animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>
@endif
