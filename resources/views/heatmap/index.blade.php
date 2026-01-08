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
                    <span class="hidden lg:block text-lg font-semibold">{{ config('app.name') }}</span>
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
                        <a href="{{ route('popular.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                            <span class="hidden lg:block">Popular</span>
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
                    <!-- Search Trigger Button -->
                    <button
                        type="button"
                        id="search-trigger"
                        onclick="openSearchDialog()"
                        class="flex items-center gap-2 px-3 py-2 bg-dark-surface border border-white/10 rounded-xl hover:bg-white/10 hover:border-neon-cyan/30 transition-colors cursor-pointer"
                        aria-label="Search movies"
                        title="Search movies (⌘K)"
                    >
                        <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <kbd class="text-xs text-text-muted bg-white/5 px-1.5 py-0.5 rounded">⌘K</kbd>
                    </button>
                </div>

                <!-- Genre Filter Chips -->
                <div class="mt-4 flex gap-2 overflow-x-auto pb-2 scrollbar-hide" id="genre-chips">
                    <!-- All (Trending) Chip -->
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
            </header>

            <!-- Movie Grid -->
            <div class="p-6">
                <div id="movie-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5 gap-4">
                    @include('heatmap.partials.movie-cards', [
                        'movies' => $movies,
                        'currentPage' => $currentPage,
                        'totalPages' => $totalPages,
                        'hasMorePages' => $hasMorePages,
                    ])
                </div>
            </div>
        </main>
    </div>

    <!-- Search Dialog -->
    <dialog
        id="search-dialog"
        class="w-[95vw] sm:w-[85vw] lg:w-[900px] max-h-[90vh] sm:max-h-[85vh] lg:max-h-[80vh] bg-dark-surface rounded-t-2xl sm:rounded-2xl border border-white/10 shadow-2xl p-0 overflow-hidden"
    >
        <!-- Sticky Search Header -->
        <div class="sticky top-0 z-10 bg-dark-card border-b border-white/10 p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-neon-cyan shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                id="search-input"
                name="q"
                placeholder="Search for movies..."
                autocomplete="off"
                class="flex-1 bg-transparent text-lg text-text-primary outline-none placeholder:text-text-muted caret-neon-cyan"
                hx-get="{{ route('search') }}"
                hx-trigger="input changed delay:300ms"
                hx-target="#search-results"
                hx-swap="innerHTML"
                hx-indicator="#search-spinner"
            >
            <svg id="search-spinner" class="htmx-indicator w-5 h-5 text-neon-cyan animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <button
                type="button"
                onclick="closeSearchDialog()"
                class="p-1.5 hover:bg-white/10 rounded-lg transition-colors shrink-0"
                aria-label="Close search"
            >
                <svg class="w-5 h-5 text-neon-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Results Area -->
        <div id="search-results" class="bg-dark-surface p-4 overflow-y-auto" style="max-height: calc(90vh - 80px);">
            <div class="flex flex-col items-center justify-center py-16 text-text-muted">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="text-lg font-medium mb-1">Search for movies</p>
                <p class="text-sm opacity-70">Start typing to find movies to add to your grid</p>
            </div>
        </div>
    </dialog>

    <script>
        // =====================
        // Search Dialog Functions
        // =====================
        function openSearchDialog() {
            const dialog = document.getElementById('search-dialog');
            dialog.showModal();
            // Focus and select input after animation
            setTimeout(() => {
                const input = document.getElementById('search-input');
                input.focus();
                input.select();
            }, 50);
        }

        function closeSearchDialog() {
            const dialog = document.getElementById('search-dialog');
            dialog.close();
            // Reset input and results
            document.getElementById('search-input').value = '';
            document.getElementById('search-results').innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 text-text-muted">
                    <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-lg font-medium mb-1">Search for movies</p>
                    <p class="text-sm opacity-70">Start typing to find movies to add to your grid</p>
                </div>
            `;
        }

        // Close dialog when clicking on backdrop
        document.getElementById('search-dialog')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSearchDialog();
            }
        });

        // Keyboard shortcut: Cmd/Ctrl + K to open search
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                openSearchDialog();
            }
        });

        // =====================
        // Movie Click Tracking
        // =====================
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

        // =====================
        // Add Movie to Grid
        // =====================
        function addMovieToGrid(button) {
            const movieId = button.dataset.movieId;
            const movieTitle = button.dataset.movieTitle;
            const posterPath = button.dataset.posterPath;
            const posterUrl = button.dataset.posterUrl;
            const voteAverage = parseFloat(button.dataset.voteAverage) || 0;

            // Close dialog first
            closeSearchDialog();

            // Check if movie already exists in grid
            const existingCard = document.querySelector(`#movie-grid [data-movie-id="${movieId}"]`);
            if (existingCard) {
                // Scroll to existing card and highlight it
                setTimeout(() => {
                    existingCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    highlightCard(existingCard);
                }, 200);
                return;
            }

            // Create new movie card HTML
            const cardHtml = createMovieCardHtml(movieId, movieTitle, posterPath, posterUrl, voteAverage);

            // Prepend to grid
            const grid = document.getElementById('movie-grid');
            grid.insertAdjacentHTML('afterbegin', cardHtml);

            // Get the newly added card and highlight
            const newCard = grid.firstElementChild;
            setTimeout(() => {
                newCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                highlightCard(newCard);
            }, 200);
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

        // =====================
        // Utility Functions
        // =====================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
