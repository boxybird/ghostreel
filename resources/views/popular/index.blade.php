<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Movie Heatmap') }} - Popular in Our App</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dark-bg text-text-primary min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-14 lg:w-64 bg-dark-surface border-r border-white/5 flex flex-col py-6 shrink-0">
            <!-- Logo -->
            <div class="px-2 lg:px-6 mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 lg:w-10 lg:h-10 rounded-xl bg-gradient-to-br from-neon-cyan to-neon-purple flex items-center justify-center">
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
                    <li>
                        <a href="{{ route('heatmap.index') }}" class="flex items-center gap-3 px-2 py-2 lg:px-3 lg:py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-text-primary transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="hidden lg:block">Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('popular.index') }}" class="flex items-center gap-3 px-2 py-2 lg:px-3 lg:py-2.5 rounded-lg bg-white/10 text-neon-cyan">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                            <span class="hidden lg:block">Popular</span>
                        </a>
                    </li>

                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="sticky top-0 z-10 bg-dark-bg/80 backdrop-blur-xl border-b border-white/5 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Popular in Our App</h1>
                        <p class="text-sm text-text-muted">Movies ranked by clicks from our users</p>
                    </div>
                </div>
            </header>

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
                            <div class="group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20">
                                <!-- Rank Badge -->
                                <div class="absolute top-2 left-2 z-20 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                                    {{ $index === 0 ? 'bg-yellow-400 text-dark-bg' : ($index === 1 ? 'bg-gray-300 text-dark-bg' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-dark-surface text-text-primary')) }}">
                                    {{ $index + 1 }}
                                </div>

                                <!-- Click Count Badge -->
                                <div class="absolute top-2 right-2 z-20 px-2 py-1 rounded-full text-xs font-bold
                                    {{ $movie['click_count'] > 5 ? 'bg-neon-pink text-white' : ($movie['click_count'] > 2 ? 'bg-neon-orange text-dark-bg' : 'bg-neon-cyan text-dark-bg') }}">
                                    {{ $movie['click_count'] }} {{ $movie['click_count'] === 1 ? 'click' : 'clicks' }}
                                </div>

                                <!-- Poster -->
                                @if($movie['poster_url'])
                                    <img
                                        src="{{ $movie['poster_url'] }}"
                                        alt="{{ $movie['movie_title'] }}"
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

                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="absolute bottom-0 left-0 right-0 p-3">
                                        <h3 class="text-sm font-semibold line-clamp-2">{{ $movie['movie_title'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
