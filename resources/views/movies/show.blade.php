<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $movie->title }} - {{ config('app.name', 'Movie Heatmap') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dark-bg text-text-primary min-h-screen">
    <!-- Hero Section with Backdrop -->
    <div class="relative">
        <!-- Backdrop Image -->
        @if($backdropUrl)
            <div class="absolute inset-0 h-[50vh] md:h-[60vh]">
                <img
                    src="{{ $backdropUrl }}"
                    alt="{{ $movie->title }} backdrop"
                    class="w-full h-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-dark-bg/80 to-dark-bg/30"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-dark-bg/90 via-transparent to-dark-bg/50"></div>
            </div>
        @else
            <div class="absolute inset-0 h-[50vh] md:h-[60vh] bg-gradient-to-b from-dark-surface to-dark-bg"></div>
        @endif

        <!-- Back Navigation -->
        <div class="relative z-10 p-4 md:p-6">
            <a
                href="{{ route('heatmap.index') }}"
                class="inline-flex items-center gap-2 px-3 py-2 bg-dark-bg/60 backdrop-blur-sm border border-white/10 rounded-lg text-text-muted hover:text-text-primary hover:bg-white/10 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back</span>
            </a>
        </div>

        <!-- Hero Content -->
        <div class="relative z-10 px-4 md:px-8 lg:px-16 pt-8 md:pt-16 pb-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row gap-6 md:gap-10">
                    <!-- Poster -->
                    <div class="shrink-0 mx-auto md:mx-0">
                        @if($posterUrl)
                            <img
                                src="{{ $posterUrl }}"
                                alt="{{ $movie->title }}"
                                class="w-48 md:w-64 lg:w-72 rounded-xl shadow-2xl shadow-black/50"
                            >
                        @else
                            <div class="w-48 md:w-64 lg:w-72 aspect-[2/3] rounded-xl bg-dark-surface flex items-center justify-center">
                                <svg class="w-16 h-16 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Movie Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-3">{{ $movie->title }}</h1>

                        @if($details && $details['tagline'])
                            <p class="text-lg text-text-muted italic mb-4">"{{ $details['tagline'] }}"</p>
                        @endif

                        <!-- Meta Info -->
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 md:gap-4 text-sm mb-6">
                            @if($movie->release_date)
                                <span class="text-text-muted">{{ $movie->release_date->format('Y') }}</span>
                            @endif

                            @if($details && $details['runtime'])
                                <span class="text-text-muted">{{ floor($details['runtime'] / 60) }}h {{ $details['runtime'] % 60 }}m</span>
                            @endif

                            @if($movie->vote_average > 0)
                                <div class="flex items-center gap-1 px-2 py-1 bg-yellow-400/20 rounded-full">
                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="font-medium text-yellow-400">{{ number_format($movie->vote_average, 1) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Genres -->
                        @if($details && count($details['genres']) > 0)
                            <div class="flex flex-wrap justify-center md:justify-start gap-2 mb-6">
                                @foreach($details['genres'] as $genre)
                                    <span class="px-3 py-1 bg-white/10 border border-white/10 rounded-full text-sm text-text-muted">
                                        {{ $genre['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Community Stats -->
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 mb-6">
                            <div class="flex items-center gap-2 px-4 py-2 bg-neon-cyan/10 border border-neon-cyan/30 rounded-xl">
                                <svg class="w-5 h-5 text-neon-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <div>
                                    <span class="text-xl font-bold text-neon-cyan">{{ $clickCount }}</span>
                                    <span class="text-sm text-text-muted ml-1">views today</span>
                                </div>
                            </div>

                            @if($totalClickCount > $clickCount)
                                <div class="flex items-center gap-2 px-4 py-2 bg-neon-purple/10 border border-neon-purple/30 rounded-xl">
                                    <svg class="w-5 h-5 text-neon-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                    </svg>
                                    <div>
                                        <span class="text-xl font-bold text-neon-purple">{{ $totalClickCount }}</span>
                                        <span class="text-sm text-text-muted ml-1">all time</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Log View Button -->
                        <button
                            type="button"
                            id="log-view-btn"
                            onclick="logMovieView()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-neon-cyan text-dark-bg font-semibold rounded-xl hover:bg-neon-cyan/90 transition-all duration-200 hover:scale-105 hover:shadow-lg hover:shadow-neon-cyan/30"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Log View to Heatmap</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="px-4 md:px-8 lg:px-16 py-8 md:py-12">
        <div class="max-w-7xl mx-auto">
            <!-- Overview -->
            @if($movie->overview)
                <section class="mb-10 md:mb-12">
                    <h2 class="text-xl md:text-2xl font-bold mb-4">Overview</h2>
                    <p class="text-text-muted leading-relaxed max-w-4xl">{{ $movie->overview }}</p>
                </section>
            @endif

            <!-- Cast -->
            @if(count($cast) > 0)
                <section class="mb-10 md:mb-12">
                    <h2 class="text-xl md:text-2xl font-bold mb-4">Cast</h2>
                    <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
                        @foreach($cast as $person)
                            <div class="shrink-0 w-28 md:w-32 text-center">
                                @if($person['profile_url'])
                                    <img
                                        src="{{ $person['profile_url'] }}"
                                        alt="{{ $person['name'] }}"
                                        class="w-20 h-20 md:w-24 md:h-24 mx-auto rounded-full object-cover mb-2 border-2 border-white/10"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-20 h-20 md:w-24 md:h-24 mx-auto rounded-full bg-dark-surface flex items-center justify-center mb-2 border-2 border-white/10">
                                        <svg class="w-8 h-8 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                                <p class="text-sm font-medium truncate">{{ $person['name'] }}</p>
                                <p class="text-xs text-text-muted truncate">{{ $person['character'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Similar Movies -->
            @if(count($similarMovies) > 0)
                <section>
                    <h2 class="text-xl md:text-2xl font-bold mb-4">Similar Movies</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($similarMovies as $similar)
                            @if($similar['db_id'])
                                <a
                                    href="{{ route('movies.show', $similar['db_id']) }}"
                                    class="group relative rounded-xl overflow-hidden bg-dark-card transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-neon-cyan/20"
                                >
                            @else
                                <div class="group relative rounded-xl overflow-hidden bg-dark-card opacity-60">
                            @endif
                                @if($similar['poster_url'])
                                    <img
                                        src="{{ $similar['poster_url'] }}"
                                        alt="{{ $similar['title'] }}"
                                        class="w-full aspect-[2/3] object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full aspect-[2/3] bg-dark-surface flex items-center justify-center">
                                        <svg class="w-10 h-10 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif

                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="absolute bottom-0 left-0 right-0 p-3">
                                        <h3 class="text-sm font-semibold line-clamp-2">{{ $similar['title'] }}</h3>
                                        @if($similar['vote_average'] > 0)
                                            <div class="flex items-center gap-1 mt-1">
                                                <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                <span class="text-xs text-text-muted">{{ number_format($similar['vote_average'], 1) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @if($similar['db_id'])
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        async function logMovieView() {
            const button = document.getElementById('log-view-btn');
            const originalContent = button.innerHTML;

            // Show loading state
            button.disabled = true;
            button.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Logging...</span>
            `;

            try {
                const response = await fetch('{{ route('heatmap.click') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        tmdb_movie_id: {{ $movie->tmdb_id }},
                        movie_title: @json($movie->title),
                        poster_path: @json($movie->poster_path),
                    }),
                });

                if (response.ok) {
                    // Show success state
                    button.classList.remove('bg-neon-cyan', 'hover:bg-neon-cyan/90');
                    button.classList.add('bg-green-500', 'hover:bg-green-500/90');
                    button.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>View Logged!</span>
                    `;

                    // Reset after 2 seconds
                    setTimeout(() => {
                        button.disabled = false;
                        button.classList.remove('bg-green-500', 'hover:bg-green-500/90');
                        button.classList.add('bg-neon-cyan', 'hover:bg-neon-cyan/90');
                        button.innerHTML = originalContent;
                    }, 2000);
                } else {
                    throw new Error('Failed to log view');
                }
            } catch (error) {
                console.error('Failed to log view:', error);
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        }
    </script>
</body>
</html>
