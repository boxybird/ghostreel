{{-- Movie cards partial for HTMX Load More --}}

{{-- OOB Swap: Reset genre chips to "All" active state (only for HTMX requests on page 1) --}}
@if($currentPage === 1 && isset($genres) && request()->header('HX-Request'))
<div id="genre-chips" hx-swap-oob="true" class="mt-4 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <button
        type="button"
        hx-get="{{ route('trending.index') }}"
        hx-target="#movie-grid"
        hx-swap="innerHTML"
        class="genre-chip genre-chip-active shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
        data-genre-id="all"
    >
        All
    </button>
    @foreach($genres as $genre)
        <button
            type="button"
            hx-get="{{ route('genres.movies.index', $genre['id']) }}"
            hx-target="#movie-grid"
            hx-swap="innerHTML"
            class="genre-chip shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
            data-genre-id="{{ $genre['id'] }}"
        >
            {{ $genre['name'] }}
        </button>
    @endforeach
</div>
@endif

@foreach($movies as $movie)
    <x-movie-card
        :movie="$movie"
        :clickable="true"
        :show-ghost-action="true"
        :show-heatmap-glow="true"
        :show-click-badge="true"
        click-badge-label="view"
        :show-rating="true"
    />
@endforeach

{{-- Load More Button (replaces itself with next batch) --}}
@if($hasMorePages)
    <div id="load-more-container" class="col-span-full flex justify-center py-6">
        <button
            type="button"
            hx-get="{{ route('trending.index') }}?page={{ $currentPage + 1 }}"
            hx-target="#load-more-container"
            hx-swap="outerHTML"
            hx-indicator="#load-more-spinner"
            class="group flex items-center gap-2 px-6 py-3 bg-dark-surface border border-white/10 rounded-xl hover:bg-white/10 hover:border-neon-cyan/30 transition-colors"
        >
            <span class="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors">Load More Movies</span>
            <svg id="load-more-spinner" class="htmx-indicator w-4 h-4 text-neon-cyan animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>
@endif
