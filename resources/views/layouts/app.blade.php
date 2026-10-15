<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Zaffico Monitoring System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 shadow-lg fixed h-screen overflow-y-auto z-20" style="background: linear-gradient(180deg, #27ae60 0%, #229954 100%);">
            <!-- Logo -->
            <div class="px-6 py-8 border-b border-green-600/30">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                        <!-- Logo Image - Replace with your company logo -->
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-md overflow-hidden">
                            <img src="{{ asset('images/image.png') }}" alt="Zaffico Logo" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center" style="display: none;">
                                <i class="fas fa-heartbeat text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">Zaffico</h1>
                            <p class="text-xs text-green-100">Monitoring System</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 pb-20">
                <a href="{{ route('dashboard') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(Route::currentRouteName() === 'dashboard') text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="{{ route('applications') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'applications')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-server w-5"></i>
                    <span class="font-medium">Applications</span>
                </a>
                <a href="{{ route('checks.index') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'checks')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-stethoscope w-5"></i>
                    <span class="font-medium">Monitoring Checks</span>
                </a>
                <a href="{{ route('incidents.index') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'incidents')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-exclamation-circle w-5"></i>
                    <span class="font-medium">Incidents</span>
                </a>
                <a href="{{ route('alerts') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'alerts')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-bell w-5"></i>
                    <span class="font-medium">Alert Rules</span>
                </a>
                <a href="{{ route('maintenance.index') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'maintenance')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-wrench w-5"></i>
                    <span class="font-medium">Maintenance</span>
                </a>
                <a href="{{ route('uptime.index') }}" class="px-6 py-3 flex items-center gap-3 text-green-100 hover:text-white hover:bg-green-600/30 transition block @if(str_contains(Route::currentRouteName(), 'uptime')) text-white bg-green-600/40 border-r-4 border-white shadow-sm @endif">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="font-medium">Reports</span>
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-green-600/30" style="background: rgba(34, 153, 84, 0.3);">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white font-bold text-sm border border-white/20">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-green-100 truncate">{{ auth()->user()->role ?? 'User' }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between text-white">
                        <span class="text-sm">Guest</span>
                        <a href="{{ route('login') }}" class="text-sm underline">Login</a>
                    </div>
                @endauth
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 ml-64 flex flex-col overflow-hidden">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-8 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">@yield('page_title', 'Dashboard')</h2>
                    <div class="flex items-center gap-4">
                        <!-- Real-time Clock -->
                        <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg">
                            <i class="fas fa-clock text-blue-600"></i>
                            <span id="realTimeClock" class="text-sm font-mono text-blue-800"></span>
                            <span class="text-xs text-blue-600" id="timezone"></span>
                        </div>
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-bell text-gray-600"></i>
                        </button>
                        <div class="w-1 h-8 bg-gray-200"></div>
                        <div class="flex items-center gap-2">
                            @auth
                                <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="text-sm text-blue-600 underline">Login</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="px-8 py-6">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-3">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-3">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Real-time Clock Script -->
    <script>
        // Update real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false, 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            const dateString = now.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            });
            
            document.getElementById('realTimeClock').textContent = `${dateString} ${timeString}`;
            document.getElementById('timezone').textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
        }
        
        // Update immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
        
        // Auto-refresh data every 30 seconds (if page supports it)
        @if(View::hasSection('auto_refresh'))
            @yield('auto_refresh')
        @endif
        
        // Auto-reload page every 10 seconds
        setTimeout(function() {
            window.location.reload();
        },10000); // 10 seconds = 10000 milliseconds
    </script>
    
    @stack('scripts')
</body>
</html>
