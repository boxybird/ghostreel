<x-layouts.app :recent-views="$recentViews" title="Who's Watching?">
    <x-slot:hero>
        <!-- Welcome Hero (dismissible per session) -->
        <div
            id="welcome-hero"
            class="mx-6 mt-4 px-4 py-3 bg-white/5 border border-white/10 rounded-xl flex items-center justify-between gap-4"
            style="display: none;"
        >
            <p class="text-sm text-text-muted">
                <span class="text-white font-medium">Log it. See what's hot.</span>
                Click the eye icon on any movie to track your watch and shape the heatmap — no sign-up needed.
            </p>
            <button
                type="button"
                onclick="dismissWelcomeHero()"
                class="text-text-muted hover:text-white transition-colors shrink-0 cursor-pointer"
                aria-label="Dismiss"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <script>
            function dismissWelcomeHero() {
                document.getElementById('welcome-hero').style.display = 'none';
                sessionStorage.setItem('welcomeHeroDismissed', 'true');
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (!sessionStorage.getItem('welcomeHeroDismissed')) {
                    document.getElementById('welcome-hero').style.display = 'flex';
                }
            });
        </script>
    </x-slot>

    <x-slot:heading>
        <h1 class="text-2xl font-bold">Trending Now</h1>
        <p class="text-sm text-text-muted">See what everyone's watching</p>
    </x-slot>

    <x-slot:actions>
        <!-- Search Trigger Button -->
        <button
            type="button"
            id="search-trigger"
            onclick="openSearchDialog()"
            class="flex items-center gap-2 px-3 py-2 bg-dark-surface border border-white/10 rounded-xl hover:bg-white/10 hover:border-neon-cyan/30 transition-colors cursor-pointer"
            aria-label="Search movies"
            title="Search movies (Cmd+K)"
        >
            <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <kbd class="hidden sm:inline-block text-xs text-text-muted bg-white/5 px-1.5 py-0.5 rounded">Cmd+K</kbd>
        </button>
    </x-slot>

    <x-slot:subheader>
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
    </x-slot>

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

    <!-- Search Dialog -->
    <x-search-dialog />
</x-layouts.app>
