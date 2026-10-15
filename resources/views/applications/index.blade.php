@extends('layouts.app')

@section('page_title', 'Applications')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time updates for applications list (no page reload)
    let consecutiveFailures = 0;
    const MAX_FAILURES = 3;
    const POLL_INTERVAL = 5000; // 5 seconds for faster network failure detection
    
    function fetchApplicationsUpdate() {
        // Chrome-specific: Don't rely on navigator.onLine - it's unreliable
        
        // Add timestamp to prevent Chrome caching
        const timestamp = new Date().getTime();
        const url = '{{ route("api.applications.index") }}?_t=' + timestamp;
        
        // Create abort controller for better timeout handling
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
        }, 8000); // 8 second timeout for faster detection
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            cache: 'no-store', // Prevent Chrome from caching
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateApplicationsList(data.data.applications);
                consecutiveFailures = 0;
            }
            return data;
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error fetching applications:', error);
            
            // Detect network errors and mark all apps as down
            const isNetworkError = 
                error.name === 'AbortError' ||
                error.name === 'TypeError' ||
                error.message.includes('Failed to fetch') ||
                error.message.includes('NetworkError');
            
            if (isNetworkError) {
                console.warn('Network error detected - marking all apps as down');
                markAllApplicationsAsDown();
            }
            
            consecutiveFailures++;
            if (consecutiveFailures >= MAX_FAILURES) {
                console.warn('Too many consecutive failures. Pausing updates.');
            }
            throw error;
        });
    }
    
    /**
     * Mark all applications as down when network fails
     */
    function markAllApplicationsAsDown() {
        const cards = document.querySelectorAll('[data-app-id]');
        cards.forEach(card => {
            const statusBadge = card.querySelector('.px-3.py-1.rounded-full');
            if (statusBadge) {
                statusBadge.className = 'px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
                statusBadge.textContent = 'DOWN';
            }
            
            const border = card.querySelector('.border-l-4');
            if (border) {
                border.className = 'border-l-4 border-red-500';
            }
        });
    }
    
    function updateApplicationsList(applications) {
        applications.forEach(app => {
            // Find the application card by ID
            const card = document.querySelector(`[data-app-id="${app.id}"]`);
            if (!card) return;
            
            // Update status badge
            const statusBadge = card.querySelector('.px-3.py-1.rounded-full');
            if (statusBadge) {
                statusBadge.className = `px-3 py-1 rounded-full text-xs font-medium ${app.status === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                statusBadge.innerHTML = `<i class="fas ${app.status === 'up' ? 'fa-check-circle' : 'fa-times-circle'} mr-1"></i>${app.status.charAt(0).toUpperCase() + app.status.slice(1)}`;
            }
            
            // Update border color
            const borderClass = app.status === 'up' ? 'border-l-green-500' : 'border-l-red-500';
            card.className = card.className.replace(/border-l-\w+-500/, borderClass);
            
            // Update last checked
            const lastCheckedEl = card.querySelector('[data-last-checked]');
            if (lastCheckedEl && app.last_checked_at) {
                const date = new Date(app.last_checked_at);
                lastCheckedEl.textContent = date.toLocaleString();
            }
            
            // Add stale indicator
            if (app.is_stale) {
                card.classList.add('opacity-75');
            } else {
                card.classList.remove('opacity-75');
            }
        });
    }
    
    // Start polling
    fetchApplicationsUpdate();
    setInterval(function() {
        // Chrome-specific: Don't rely on navigator.onLine - try to fetch anyway
        if (consecutiveFailures < MAX_FAILURES) {
            fetchApplicationsUpdate();
        }
    }, POLL_INTERVAL);
    
    // Listen for online/offline events
    window.addEventListener('online', () => {
        consecutiveFailures = 0;
        fetchApplicationsUpdate();
    });
});
</script>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header with Action Button -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Applications</h1>
            <p class="text-gray-600 mt-1">Manage all monitored applications</p>
        </div>
        <a href="{{ route('applications.create') }}" class="btn-primary flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Application
        </a>
    </div>

    <!-- Applications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($applications as $app)
            <div data-app-id="{{ $app->id }}" class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden border-l-4 {{ $app->status == 'up' ? 'border-l-green-500' : 'border-l-red-500' }}">
                <!-- Header -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $app->name }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <i class="fas fa-network-wired"></i> {{ $app->ip_address }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $app->status == 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $app->status == 'up' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ ucfirst($app->status) }}
                        </span>
                    </div>
                </div>

                <!-- Details -->
                <div class="p-4 space-y-2">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Protocol</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ ucfirst($app->protocol ?? 'N/A') }}</p>
                    </div>
                    @if($app->endpoint)
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Endpoint</p>
                            <p class="text-xs text-gray-800 mt-0.5">{{ $app->endpoint }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Last Checked</p>
                        <p class="text-xs text-gray-800 mt-0.5">
                            @if($app->last_checked_at)
                                <span title="{{ $app->last_checked_at->format('M d, Y H:i:s') }} ({{ config('app.timezone') }})">
                                    {{ $app->last_checked_at->diffForHumans() }}
                                </span>
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-200 flex gap-1.5">
                    <a href="{{ route('applications.show', $app->id) }}" class="flex-1 px-2 py-1.5 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition text-xs font-medium text-center">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('applications.edit', $app->id) }}" class="flex-1 px-2 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition text-xs font-medium text-center">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('applications.destroy', $app->id) }}" method="POST" style="display: none;" id="delete-{{ $app->id }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button onclick="confirmDelete({{ $app->id }})" class="flex-1 px-2 py-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 transition text-xs font-medium">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                <p class="text-gray-600 text-sm font-semibold">No applications yet</p>
                <p class="text-gray-500 text-xs mt-1">Create your first application to start monitoring</p>
                <a href="{{ route('applications.create') }}" class="btn-primary mt-4 inline-block text-sm">
                    <i class="fas fa-plus"></i> Create Application
                </a>
            </div>
        @endforelse
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this application? This action cannot be undone.')) {
            document.getElementById('delete-' + id).submit();
        }
    }
</script>
@endsection
