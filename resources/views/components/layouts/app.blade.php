@props([
    'title' => null,
    'recentViews' => collect(),
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'Movie Heatmap') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-dark-bg text-text-primary min-h-screen">
    <div class="flex min-h-screen">
        <x-sidebar :recent-views="$recentViews" />

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            @if(isset($hero))
                {{ $hero }}
            @endif

            <!-- Header -->
            <header class="sticky top-0 z-10 bg-dark-bg/80 backdrop-blur-xl border-b border-white/5 px-6 py-4">
                <div class="flex items-center justify-between">
                    @if(isset($heading))
                        <div>
                            {{ $heading }}
                        </div>
                    @endif

                    @if(isset($actions))
                        <div>
                            {{ $actions }}
                        </div>
                    @endif
                </div>

                @if(isset($subheader))
                    {{ $subheader }}
                @endif
            </header>

            <!-- Page Content -->
            {{ $slot }}
        </main>
        
        <!-- HTMX Error Message -->
        <div id="htmx-error-message" class="hidden fixed top-4 right-4 z-50 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg">
            Error occurred
        </div>
    </div>
</body>
</html>
