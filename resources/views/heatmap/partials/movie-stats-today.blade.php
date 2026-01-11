@props(['clickCount', 'oobSwap' => false])

<div id="movie-stats-today" @if($oobSwap) hx-swap-oob="true" @endif class="flex items-center gap-2 px-4 py-2 bg-neon-cyan/10 border border-neon-cyan/30 rounded-full">
    <svg class="w-5 h-5 text-neon-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 10h.01"/>
        <path d="M15 10h.01"/>
        <path d="M12 2a8 8 0 0 0-8 8v12l3-3 2.5 2.5L12 19l2.5 2.5L17 19l3 3V10a8 8 0 0 0-8-8z"/>
    </svg>
    <span class="text-neon-cyan font-semibold">{{ $clickCount }}</span>
    <span class="text-sm text-text-muted">views today</span>
</div>
