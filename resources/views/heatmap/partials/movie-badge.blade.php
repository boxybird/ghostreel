@php
    $hasRank = isset($rank) && $rank !== null;
    $hasClicks = isset($showClickBadge) && $showClickBadge && $clickCount > 0;
    $isVisible = $hasRank || $hasClicks;
@endphp

{{-- Movie Stats Badge (Rank + Clicks) --}}
<div 
    id="movie-badge-{{ $movieId }}" 
    @class([
        'absolute top-2 left-2 z-20 flex items-center rounded-full overflow-hidden border border-white/10 shadow-lg backdrop-blur-sm transition-all duration-300',
        'opacity-0 pointer-events-none scale-95' => !$isVisible,
        'opacity-100 scale-100' => $isVisible,
    ])
>
    {{-- Rank Segment --}}
    @if($hasRank)
        <div class="px-2.5 py-1 text-xs font-bold {{ $rankClasses ?? '' }}">
            {{ $rank }}
        </div>
    @endif

    {{-- Click Count Segment --}}
    @if($hasClicks)
        <div class="px-2 py-1 text-[10px] uppercase font-bold {{ $badgeClasses ?? '' }} {{ $hasRank ? 'border-l border-dark-bg/20' : '' }}">
            {{ $clickCount }} {{ $clickCount === 1 ? $clickBadgeLabel : Str::plural($clickBadgeLabel) }}
        </div>
    @endif
</div>
