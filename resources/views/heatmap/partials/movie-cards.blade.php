{{-- Movie cards partial for HTMX Load More --}}

{{-- OOB Swap: Reset genre chips to "All" active state (only for HTMX requests on page 1) --}}
@if($currentPage === 1 && isset($genres) && request()->header('HX-Request'))
<div id="genre-chips" hx-swap-oob="true" class="mt-4 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <button
        type="button"
        hx-get="{{ route('heatmap.trending') }}"
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
            hx-get="{{ route('genres.show', $genre['id']) }}"
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
    <button
        type="button"
        class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
        data-movie-id="{{ $movie['id'] }}"
        data-movie-title="{{ $movie['title'] }}"
        data-poster-path="{{ $movie['poster_path'] }}"
        onclick="logMovieClick(this)"
    >
        {{-- Heatmap Glow Effect --}}
        @if($movie['click_count'] > 0)
            <div class="absolute inset-0 z-10 pointer-events-none rounded-xl"
                 style="box-shadow: inset 0 0 {{ min($movie['click_count'] * 8, 60) }}px {{ $movie['click_count'] > 5 ? 'var(--color-neon-pink)' : ($movie['click_count'] > 2 ? 'var(--color-neon-orange)' : 'var(--color-neon-cyan)') }}40;">
            </div>
        @endif

        {{-- Poster --}}
        @if($movie['poster_url'])
            <img
                src="{{ $movie['poster_url'] }}"
                alt="{{ $movie['title'] }}"
                class="w-full aspect-[2/3] object-cover"
                loading="lazy"
            >
        @else
            <div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
                <svg class="w-12 h-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
        @endif

        {{-- Click Count Badge --}}
        @if($movie['click_count'] > 0)
            <div class="absolute top-2 right-2 z-20 px-2 py-1 rounded-full text-xs font-bold
                {{ $movie['click_count'] > 5 ? 'bg-neon-pink text-white' : ($movie['click_count'] > 2 ? 'bg-neon-orange text-dark-bg' : 'bg-neon-cyan text-dark-bg') }}">
                {{ $movie['click_count'] }} {{ $movie['click_count'] === 1 ? 'view' : 'views' }}
            </div>
        @endif

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <div class="absolute bottom-0 left-0 right-0 p-3">
                <h3 class="text-sm font-semibold line-clamp-2">{{ $movie['title'] }}</h3>
                @if($movie['vote_average'] > 0)
                    <div class="flex items-center gap-1 mt-1">
                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="text-xs text-text-muted">{{ number_format($movie['vote_average'], 1) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </button>
@endforeach

{{-- Load More Button (replaces itself with next batch) --}}
@if($hasMorePages)
    <div id="load-more-container" class="col-span-full flex justify-center py-6">
        <button
            type="button"
            hx-get="{{ route('heatmap.trending') }}?page={{ $currentPage + 1 }}"
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
