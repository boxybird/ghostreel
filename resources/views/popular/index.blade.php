<x-layouts.app :recent-views="$recentViews" title="Popular in Our App">
    <x-slot:heading>
        <h1 class="text-2xl font-bold">Popular in Our App</h1>
        <p class="text-sm text-text-muted">Movies ranked by clicks from our users</p>
    </x-slot>

    <!-- Movie Grid -->
    <div class="p-6">
        @if($movies->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 text-text-muted">
                <svg class="w-20 h-20 mb-6 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                </svg>
                <p class="text-xl font-medium mb-2">No popular movies yet</p>
                <p class="text-sm opacity-70 mb-6">Start clicking on movies to see what's popular!</p>
                <a href="{{ route('heatmap.index') }}" class="px-4 py-2 bg-neon-cyan text-dark-bg rounded-lg font-medium hover:bg-neon-cyan/90 transition-colors">
                    Browse Trending Movies
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5 gap-4">
                @foreach($movies as $index => $movie)
                    <x-movie-card
                        :movie="$movie"
                        :show-ghost-action="true"
                        :show-heatmap-glow="true"
                        :show-click-badge="true"
                        click-badge-label="view"
                        :rank="$index + 1"
                    />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
