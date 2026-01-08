{{-- Search Dialog Component --}}
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
