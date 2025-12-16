<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            @include('partials.header')
            @include('partials.sidebar')

            <div class="pt-16 transition-all">
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @if(session('success') || session('error'))
            <div
                id="flash-toast"
                class="fixed top-4 right-4 z-50 max-w-sm w-full sm:w-96 bg-white shadow-lg rounded-lg border {{ session('error') ? 'border-red-200' : 'border-green-200' }} overflow-hidden"
            >
                <div class="flex items-start px-4 py-3">
                    <div class="flex-shrink-0 mt-0.5">
                        @if(session('error'))
                            <i class="bi bi-x-circle-fill text-red-500 text-lg"></i>
                        @else
                            <i class="bi bi-check-circle-fill text-green-500 text-lg"></i>
                        @endif
                    </div>
                    <div class="ml-3 text-sm text-gray-800">
                        {{ session('error') ?? session('success') }}
                    </div>
                    <button
                        type="button"
                        aria-label="Close"
                        class="ml-auto text-gray-400 hover:text-gray-600"
                        onclick="document.getElementById('flash-toast')?.remove();"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="h-1 bg-gray-100">
                    <div id="flash-toast-progress" class="h-1 {{ session('error') ? 'bg-red-400' : 'bg-green-400' }} w-full"></div>
                </div>
            </div>

            <script>
                (() => {
                    const toast = document.getElementById('flash-toast');
                    const progress = document.getElementById('flash-toast-progress');
                    if (!toast || !progress) return;
                    let duration = 4000;
                    progress.style.transition = `width ${duration}ms linear`;
                    requestAnimationFrame(() => {
                        progress.style.width = '0%';
                    });
                    setTimeout(() => toast.remove(), duration + 200);
                })();
            </script>
        @endif
    </body>
</html>
