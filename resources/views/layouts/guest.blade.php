<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Zaffico Monitoring System')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" style="background: linear-gradient(135deg, #f0f9f4 0%, #e8f5e9 100%);">
            <!-- Logo and Branding -->
            <div class="mb-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center shadow-lg overflow-hidden">
                        <img src="{{ asset('images/image.png') }}" alt="Zaffico Logo" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-full bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center" style="display: none;">
                            <i class="fas fa-heartbeat text-white text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold" style="color: #27ae60;">Zaffico</h1>
                        <p class="text-sm text-gray-600">Monitoring System</p>
                    </div>
                </a>
            </div>

            <!-- Auth Card -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-xl rounded-lg overflow-hidden border border-gray-100">
                {{ $slot }}
            </div>

            <!-- Footer Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('welcome') }}" class="text-sm text-gray-600 hover:text-green-600 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Status Page
                </a>
            </div>
        </div>
    </body>
</html>
