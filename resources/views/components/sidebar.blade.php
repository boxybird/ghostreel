@props([
    'recentViews' => collect(),
])

@php
$navItems = [
    [
        'route' => 'heatmap.index',
        'label' => 'Trending',
        'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    ],
    [
        'route' => 'popular.index',
        'label' => 'Popular',
        'icon' => 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
    ],
];
@endphp

<aside class="w-14 lg:w-64 bg-dark-surface border-r border-white/5 flex flex-col py-6 shrink-0">
    <!-- Logo -->
    <div class="px-2 lg:px-6 mb-8">
        <div class="flex items-center justify-center gap-3 lg:justify-start">
            <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-xl bg-linear-to-br from-neon-cyan to-neon-purple flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="hidden lg:block text-lg font-semibold">{{ config('app.name') }}</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-1 lg:px-4">
        <ul class="space-y-1">
            @foreach ($navItems as $item)
                @php
                    $isActive = request()->routeIs($item['route']);
                @endphp
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex items-center justify-center gap-3 px-2 py-2 lg:justify-start lg:px-3 lg:py-2.5 rounded-lg transition-colors',
                            'bg-white/10 text-neon-cyan' => $isActive,
                            'text-text-muted hover:bg-white/5 hover:text-text-primary' => ! $isActive,
                        ])
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="hidden lg:block">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Recent Views Sidebar -->
    <div class="hidden lg:block px-2 lg:px-4 mt-6">
        <h3 class="hidden lg:block text-xs font-medium text-text-muted uppercase tracking-wider px-3 mb-3">Recent Views</h3>
        @include('heatmap.partials.recent-views', ['recentViews' => $recentViews])
    </div>
</aside>
