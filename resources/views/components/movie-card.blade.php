@props([
    'movie',
    'clickable' => true,
    'showGhostAction' => false,
    'showHeatmapGlow' => false,
    'showClickBadge' => false,
    'clickBadgeLabel' => 'views',
    'rank' => null,
    'showRating' => true,
    'variant' => 'default',
])

@php
    // Normalize movie data - support both array and object access
    $movieId = data_get($movie, 'id') ?? data_get($movie, 'tmdb_movie_id');
    $dbId = data_get($movie, 'db_id');
    $title = data_get($movie, 'title') ?? data_get($movie, 'movie_title');
    $posterUrl = data_get($movie, 'poster_url');
    $posterPath = data_get($movie, 'poster_path');
    $voteAverage = data_get($movie, 'vote_average', 0);
    $clickCount = data_get($movie, 'click_count', 0);
    $releaseDate = data_get($movie, 'release_date');

    // Determine if card should be linked
    $isLinked = $clickable && $dbId;

    // Heatmap glow color based on click count
    $glowColor = match(true) {
        $clickCount > 5 => 'var(--color-neon-pink)',
        $clickCount > 2 => 'var(--color-neon-orange)',
        default => 'var(--color-neon-cyan)',
    };
    $glowIntensity = min($clickCount * 8, 60);

    // Click badge color
    $badgeClasses = match(true) {
        $clickCount > 5 => 'bg-neon-pink text-white',
        $clickCount > 2 => 'bg-neon-orange text-dark-bg',
        default => 'bg-neon-cyan text-dark-bg',
    };

    // Rank badge styling (gold, silver, bronze for top 3)
    $rankClasses = match($rank) {
        1 => 'bg-yellow-400 text-dark-bg',
        2 => 'bg-gray-300 text-dark-bg',
        3 => 'bg-amber-600 text-white',
        default => 'bg-dark-surface text-text-primary',
    };
@endphp

{{-- Minimal variant (for similar movies section) --}}
@if($variant === 'minimal')
    @if($isLinked)
        <a href="{{ route('movies.show', $dbId) }}" class="shrink-0 group">
    @else
        <div class="shrink-0 {{ !$dbId ? 'opacity-60' : '' }}">
    @endif
        <div class="w-24 md:w-28 rounded-lg overflow-hidden bg-dark-card transition-transform duration-200 {{ $isLinked ? 'group-hover:scale-105' : '' }}">
            @if($posterUrl)
                <img
                    src="{{ $posterUrl }}"
                    alt="{{ $title }}"
                    class="w-full aspect-[2/3] object-cover"
                    loading="lazy"
                >
            @else
                <div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
                    <svg class="w-8 h-8 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        </div>
        <p class="mt-1 text-xs text-text-muted line-clamp-1 w-24 md:w-28">{{ $title }}</p>
    @if($isLinked)
        </a>
    @else
        </div>
    @endif

{{-- Search variant (action buttons overlay) --}}
@elseif($variant === 'search')
    <div
        class="search-result-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20"
        data-movie-id="{{ $movieId }}"
        data-movie-title="{{ $title }}"
        data-poster-path="{{ $posterPath }}"
        data-poster-url="{{ $posterUrl }}"
        data-vote-average="{{ $voteAverage }}"
        @if($dbId) data-db-id="{{ $dbId }}" @endif
    >
        {{-- Ghost Quick Action Button --}}
        @if($showGhostAction)
            <button
                type="button"
                onclick="logMovieClickFromSearch(this.closest('.search-result-card'));"
                class="ghost-action-btn absolute top-2 right-2 z-30 p-2 rounded-full bg-dark-bg/70 backdrop-blur-sm border border-white/10 text-text-muted hover:bg-neon-pink hover:text-white hover:border-neon-pink hover:shadow-[0_0_15px_rgba(255,0,135,0.5)] transition-all duration-300 group/ghost"
                title="Add Ghost View"
            >
                <svg class="w-4 h-4 transition-transform group-hover/ghost:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 10h.01"/>
                    <path d="M15 10h.01"/>
                    <path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/>
                </svg>
            </button>
        @endif

        {{-- Poster --}}
        @if($posterUrl)
            <img
                src="{{ $posterUrl }}"
                alt="{{ $title }}"
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

        {{-- Always visible bottom info --}}
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-dark-bg via-dark-bg/80 to-transparent p-3 pt-8">
            <h3 class="text-sm font-semibold line-clamp-1">{{ $title }}</h3>
            <div class="flex items-center gap-2 mt-1">
                @if($releaseDate)
                    <span class="text-xs text-text-muted">{{ \Carbon\Carbon::parse($releaseDate)->format('Y') }}</span>
                @endif
                @if($voteAverage > 0)
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="text-xs text-text-muted">{{ number_format($voteAverage, 1) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Hover overlay with actions --}}
        <div class="absolute inset-0 bg-dark-bg/90 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col items-center justify-center gap-3 p-4">
            <button
                type="button"
                onclick="addMovieToGrid(this.closest('.search-result-card'));"
                class="w-full px-4 py-2 bg-neon-cyan text-dark-bg rounded-lg font-medium hover:bg-neon-cyan/90 transition-colors text-sm"
            >
                Add to grid
            </button>
            @if($dbId)
                <a
                    href="{{ route('movies.show', $dbId) }}"
                    class="w-full px-4 py-2 bg-dark-surface border border-white/10 text-text-primary rounded-lg font-medium hover:bg-white/10 transition-colors text-sm text-center"
                >
                    View details
                </a>
            @endif
        </div>
    </div>

{{-- Default variant (trending, genre, popular) --}}
@else
    @if($isLinked)
        <a
            href="{{ route('movies.show', $dbId) }}"
            class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
            data-movie-id="{{ $movieId }}"
            data-movie-title="{{ $title }}"
            data-poster-path="{{ $posterPath }}"
        >
    @else
        <div
            class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20"
            data-movie-id="{{ $movieId }}"
            data-movie-title="{{ $title }}"
            data-poster-path="{{ $posterPath }}"
        >
    @endif

        {{-- Heatmap Glow Effect --}}
        @if($showHeatmapGlow && $clickCount > 0)
            <div class="absolute inset-0 z-10 pointer-events-none rounded-xl"
                 style="box-shadow: inset 0 0 {{ $glowIntensity }}px {{ $glowColor }}40;">
            </div>
        @endif

        {{-- Rank Badge (top-left) --}}
        @if($rank !== null)
            <div class="absolute top-2 left-2 z-20 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $rankClasses }}">
                {{ $rank }}
            </div>
        @endif

        {{-- Click Count Badge --}}
        @if($showClickBadge && $clickCount > 0)
            <div class="absolute top-2 {{ $rank !== null ? 'right-2' : 'left-2' }} z-20 px-2 py-1 rounded-full text-xs font-bold {{ $badgeClasses }}">
                {{ $clickCount }} {{ $clickCount === 1 ? $clickBadgeLabel : Str::plural($clickBadgeLabel) }}
            </div>
        @endif

        {{-- Ghost Quick Action Button --}}
        @if($showGhostAction)
            <button
                type="button"
                onclick="event.preventDefault(); event.stopPropagation(); logMovieClick(this.closest('.movie-card'));"
                class="ghost-action-btn absolute top-2 right-2 z-30 p-2 rounded-full bg-dark-bg/70 backdrop-blur-sm border border-white/10 text-text-muted hover:bg-neon-pink hover:text-white hover:border-neon-pink hover:shadow-[0_0_15px_rgba(255,0,135,0.5)] transition-all duration-300 group/ghost"
                title="Add Ghost View"
            >
                <svg class="w-4 h-4 transition-transform group-hover/ghost:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 10h.01"/>
                    <path d="M15 10h.01"/>
                    <path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/>
                </svg>
            </button>
        @endif

        {{-- Poster --}}
        @if($posterUrl)
            <img
                src="{{ $posterUrl }}"
                alt="{{ $title }}"
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

        {{-- Hover Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
            <div class="absolute bottom-0 left-0 right-0 p-3">
                <h3 class="text-sm font-semibold line-clamp-2">{{ $title }}</h3>
                @if($showRating && $voteAverage > 0)
                    <div class="flex items-center gap-1 mt-1">
                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="text-xs text-text-muted">{{ number_format($voteAverage, 1) }}</span>
                    </div>
                @endif
            </div>
        </div>

    @if($isLinked)
        </a>
    @else
        </div>
    @endif
@endif
