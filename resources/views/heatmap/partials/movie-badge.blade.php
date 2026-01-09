{{-- Movie Stats Badge (Rank + Clicks) --}}
<div id="movie-badge-{{ $movieId }}" class="absolute top-2 left-2 z-20 flex items-center rounded-full overflow-hidden border border-white/10 shadow-lg backdrop-blur-sm">
    {{-- Rank Segment --}}
    @if(isset($rank) && $rank !== null)
        <div class="px-2.5 py-1 text-xs font-bold {{ $rankClasses ?? '' }}">
            {{ $rank }}
        </div>
    @endif

    {{-- Click Count Segment --}}
    @if(isset($showClickBadge) && $showClickBadge && $clickCount > 0)
        <div class="px-2 py-1 text-[10px] uppercase font-bold {{ $badgeClasses ?? '' }} {{ (isset($rank) && $rank !== null) ? 'border-l border-dark-bg/20' : '' }}">
            {{ $clickCount }} {{ $clickCount === 1 ? $clickBadgeLabel : Str::plural($clickBadgeLabel) }}
        </div>
    @endif
</div>
