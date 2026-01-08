<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Movie Heatmap') }} - Who's Watching?</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dark-bg text-text-primary min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-20 lg:w-64 bg-dark-surface border-r border-white/5 flex flex-col py-6 shrink-0">
            <!-- Logo -->
            <div class="px-4 lg:px-6 mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-neon-cyan to-neon-purple flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="hidden lg:block text-lg font-semibold">Heatmap</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 lg:px-4">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('heatmap.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-neon-cyan">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="hidden lg:block">Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                            <span class="hidden lg:block">Popular</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                            <span class="hidden lg:block">Movies</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Recent Views Sidebar -->
            <div class="px-2 lg:px-4 mt-6">
                <h3 class="hidden lg:block text-xs font-medium text-text-muted uppercase tracking-wider px-3 mb-3">Recent Views</h3>
                <div id="recent-views-sidebar" class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($recentViews as $view)
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-white/5">
                            @if($view['poster_url'])
                                <img src="{{ $view['poster_url'] }}" alt="{{ $view['movie_title'] }}" class="w-8 h-12 rounded object-cover">
                            @else
                                <div class="w-8 h-12 rounded bg-dark-card flex items-center justify-center">
                                    <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="hidden lg:block flex-1 min-w-0">
                                <p class="text-xs font-medium truncate">{{ $view['movie_title'] }}</p>
                                <p class="text-xs text-text-muted">{{ $view['clicked_at'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="hidden lg:block text-xs text-text-muted px-3">No recent views</p>
                    @endforelse
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="sticky top-0 z-10 bg-dark-bg/80 backdrop-blur-xl border-b border-white/5 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Trending Now</h1>
                        <p class="text-sm text-text-muted">See what everyone's watching</p>
                    </div>
                    <div class="relative">
                        <input
                            type="text"
                            id="search-input"
                            name="q"
                            placeholder="Search movies..."
                            autocomplete="off"
                            class="w-64 px-4 py-2 pl-10 bg-dark-surface border border-white/10 rounded-xl text-sm focus:outline-none focus:border-neon-cyan/50 focus:ring-1 focus:ring-neon-cyan/25 transition-colors"
                            hx-get="{{ route('search') }}"
                            hx-trigger="input changed delay:300ms, focus"
                            hx-target="#search-results"
                            hx-swap="innerHTML"
                            hx-indicator="#search-spinner"
                            popovertarget="search-popover"
                            popovertargetaction="show"
                        >
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <svg id="search-spinner" class="htmx-indicator w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-neon-cyan animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <!-- Search Results Popover -->
                        <div
                            id="search-popover"
                            popover
                            class="absolute top-full left-0 mt-2 w-80 max-h-96 overflow-y-auto bg-dark-surface border border-white/10 rounded-xl shadow-2xl shadow-black/50 z-50"
                        >
                            <div id="search-results">
                                <div class="p-4 text-center text-text-muted text-sm">
                                    Type to search movies...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Movie Grid -->
            <div class="p-6">
                <div id="movie-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($movies as $movie)
                        <button
                            type="button"
                            class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
                            data-movie-id="{{ $movie['id'] }}"
                            data-movie-title="{{ $movie['title'] }}"
                            data-poster-path="{{ $movie['poster_path'] }}"
                            onclick="logMovieClick(this)"
                        >
                            <!-- Heatmap Glow Effect -->
                            @if($movie['click_count'] > 0)
                                <div class="absolute inset-0 z-10 pointer-events-none rounded-xl"
                                     style="box-shadow: inset 0 0 {{ min($movie['click_count'] * 8, 60) }}px {{ $movie['click_count'] > 5 ? 'var(--color-neon-pink)' : ($movie['click_count'] > 2 ? 'var(--color-neon-orange)' : 'var(--color-neon-cyan)') }}40;">
                                </div>
                            @endif

                            <!-- Poster -->
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

                            <!-- Click Count Badge -->
                            @if($movie['click_count'] > 0)
                                <div class="absolute top-2 right-2 z-20 px-2 py-1 rounded-full text-xs font-bold
                                    {{ $movie['click_count'] > 5 ? 'bg-neon-pink text-white' : ($movie['click_count'] > 2 ? 'bg-neon-orange text-dark-bg' : 'bg-neon-cyan text-dark-bg') }}">
                                    {{ $movie['click_count'] }} {{ $movie['click_count'] === 1 ? 'view' : 'views' }}
                                </div>
                            @endif

                            <!-- Overlay -->
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
                </div>
            </div>
        </main>
    </div>

    <script>
        async function logMovieClick(button) {
            const movieId = button.dataset.movieId;
            const movieTitle = button.dataset.movieTitle;
            const posterPath = button.dataset.posterPath;

            try {
                const response = await fetch('{{ route('heatmap.click') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        tmdb_movie_id: parseInt(movieId),
                        movie_title: movieTitle,
                        poster_path: posterPath || null,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    updateRecentViews(data.recent_views);
                    updateClickCount(button);
                }
            } catch (error) {
                console.error('Failed to log click:', error);
            }
        }

        function updateRecentViews(recentViews) {
            const sidebar = document.getElementById('recent-views-sidebar');
            if (!sidebar || !recentViews) return;

            sidebar.innerHTML = recentViews.map(view => `
                <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-white/5">
                    ${view.poster_url
                        ? `<img src="${view.poster_url}" alt="${view.movie_title}" class="w-8 h-12 rounded object-cover">`
                        : `<div class="w-8 h-12 rounded bg-dark-card flex items-center justify-center">
                            <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                           </div>`
                    }
                    <div class="hidden lg:block flex-1 min-w-0">
                        <p class="text-xs font-medium truncate">${view.movie_title}</p>
                        <p class="text-xs text-text-muted">${view.clicked_at}</p>
                    </div>
                </div>
            `).join('');
        }

        function updateClickCount(button) {
            let badge = button.querySelector('[class*="absolute top-2 right-2"]');
            if (badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                const newCount = currentCount + 1;
                badge.textContent = `${newCount} ${newCount === 1 ? 'view' : 'views'}`;

                // Update badge color based on count
                badge.className = badge.className.replace(/bg-neon-\w+/g, '');
                if (newCount > 5) {
                    badge.classList.add('bg-neon-pink', 'text-white');
                } else if (newCount > 2) {
                    badge.classList.add('bg-neon-orange', 'text-dark-bg');
                } else {
                    badge.classList.add('bg-neon-cyan', 'text-dark-bg');
                }
            } else {
                // Create new badge
                badge = document.createElement('div');
                badge.className = 'absolute top-2 right-2 z-20 px-2 py-1 rounded-full text-xs font-bold bg-neon-cyan text-dark-bg';
                badge.textContent = '1 view';
                button.appendChild(badge);
            }
        }

        function addMovieToGrid(button) {
            const movieId = button.dataset.movieId;
            const movieTitle = button.dataset.movieTitle;
            const posterPath = button.dataset.posterPath;
            const posterUrl = button.dataset.posterUrl;
            const voteAverage = parseFloat(button.dataset.voteAverage) || 0;

            // Check if movie already exists in grid
            const existingCard = document.querySelector(`[data-movie-id="${movieId}"]`);
            if (existingCard && existingCard.closest('#movie-grid')) {
                // Scroll to existing card and highlight it
                existingCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                highlightCard(existingCard);
                closeSearchPopover();
                return;
            }

            // Create new movie card HTML
            const cardHtml = createMovieCardHtml(movieId, movieTitle, posterPath, posterUrl, voteAverage);

            // Prepend to grid
            const grid = document.getElementById('movie-grid');
            grid.insertAdjacentHTML('afterbegin', cardHtml);

            // Get the newly added card
            const newCard = grid.firstElementChild;

            // Scroll to top and highlight
            newCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            highlightCard(newCard);

            // Close the search popover
            closeSearchPopover();

            // Clear search input
            document.getElementById('search-input').value = '';
        }

        function createMovieCardHtml(movieId, movieTitle, posterPath, posterUrl, voteAverage) {
            const posterContent = posterUrl
                ? `<img src="${posterUrl}" alt="${escapeHtml(movieTitle)}" class="w-full aspect-[2/3] object-cover" loading="lazy">`
                : `<div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
                    <svg class="w-12 h-12 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                   </div>`;

            const ratingContent = voteAverage > 0
                ? `<div class="flex items-center gap-1 mt-1">
                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-xs text-text-muted">${voteAverage.toFixed(1)}</span>
                   </div>`
                : '';

            return `
                <button
                    type="button"
                    class="movie-card group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20 focus:outline-none focus:ring-2 focus:ring-neon-cyan/50"
                    data-movie-id="${movieId}"
                    data-movie-title="${escapeHtml(movieTitle)}"
                    data-poster-path="${posterPath || ''}"
                    onclick="logMovieClick(this)"
                >
                    ${posterContent}
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <h3 class="text-sm font-semibold line-clamp-2">${escapeHtml(movieTitle)}</h3>
                            ${ratingContent}
                        </div>
                    </div>
                </button>
            `;
        }

        function highlightCard(card) {
            card.classList.add('ring-2', 'ring-neon-cyan', 'shadow-lg', 'shadow-neon-cyan/40');
            setTimeout(() => {
                card.classList.remove('ring-2', 'ring-neon-cyan', 'shadow-lg', 'shadow-neon-cyan/40');
            }, 2000);
        }

        function closeSearchPopover() {
            const popover = document.getElementById('search-popover');
            if (popover && popover.hidePopover) {
                popover.hidePopover();
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close popover when clicking outside
        document.addEventListener('click', (e) => {
            const popover = document.getElementById('search-popover');
            const searchInput = document.getElementById('search-input');
            if (popover && !popover.contains(e.target) && e.target !== searchInput) {
                closeSearchPopover();
            }
        });

        // Show popover when input has value and is focused
        document.getElementById('search-input')?.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                const popover = document.getElementById('search-popover');
                if (popover && popover.showPopover) {
                    popover.showPopover();
                }
            }
        });

        // Show popover after HTMX loads search results
        document.body.addEventListener('htmx:afterSwap', function(event) {
            if (event.detail.target.id === 'search-results') {
                const popover = document.getElementById('search-popover');
                const searchInput = document.getElementById('search-input');
                if (popover && popover.showPopover && searchInput.value.length >= 2) {
                    popover.showPopover();
                }
            }
        });
    </script>
</body>
</html>
