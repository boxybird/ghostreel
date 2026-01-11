<div id="recent-views-sidebar" @if(request()->header('HX-Request')) hx-swap-oob="true" @endif class="space-y-2 max-h-64 overflow-y-auto">
    @forelse ($recentViews as $view)
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-white/5">
            @if ($view->poster_url)
                <img src="{{ $view->poster_url }}" alt="{{ $view->movie_title }}" class="w-8 h-12 rounded object-cover">
            @else
                <div class="w-8 h-12 rounded bg-dark-card flex items-center justify-center">
                    <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
            <div class="hidden lg:block flex-1 min-w-0">
                <p class="text-xs font-medium truncate">{{ $view->movie_title }}</p>
                <p class="text-xs text-text-muted">{{ $view->clicked_at->diffForHumans() }}</p>
            </div>
        </div>
    @empty
        <p class="hidden lg:block text-xs text-text-muted px-3">No recent views</p>
    @endforelse
</div>
