@extends('layouts.app')

@section('page_title', 'Edit Application')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="{{ route('applications') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Applications
    </a>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Edit Application</h2>
            <p class="text-gray-600 text-sm mt-1">Update application settings</p>
        </div>

        <!-- Errors -->
        @if ($errors->any())
            <div class="m-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold mb-2"><i class="fas fa-exclamation-circle"></i> Please fix errors</p>
                <ul class="text-red-700 text-sm space-y-1 ml-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('applications.update', $application->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Application Name</label>
                <input type="text" name="name" value="{{ $application->name }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">IP Address</label>
                <input type="text" name="ip_address" value="{{ $application->ip_address }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Port <span class="text-gray-500 text-xs">(Optional)</span></label>
                <input type="number" name="port" value="{{ $application->port }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., 80, 443, 8080">
                <p class="text-gray-500 text-xs mt-1">Leave empty for standard ports (80 for HTTP, 443 for HTTPS)</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Protocol</label>
                <select name="protocol" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="http" {{ $application->protocol === 'http' ? 'selected' : '' }}>HTTP</option>
                    <option value="https" {{ $application->protocol === 'https' ? 'selected' : '' }}>HTTPS</option>
                    <option value="ping" {{ $application->protocol === 'ping' ? 'selected' : '' }}>Ping</option>
                    <option value="tcp" {{ $application->protocol === 'tcp' ? 'selected' : '' }}>TCP</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Endpoint <span class="text-gray-500 text-xs">(Optional)</span></label>
                <input type="text" name="endpoint" value="{{ $application->endpoint }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., /health, /api/status, /index.html">
                <p class="text-gray-500 text-xs mt-1">For HTTP/HTTPS protocols only. Defaults to root (/) if empty</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Check Interval (minutes) 
                    <span class="text-xs text-gray-500 font-normal">(Informational only - Real-time mode checks all apps every minute)</span>
                </label>
                <input type="number" name="check_interval" value="{{ $application->check_interval ?? 1 }}" readonly
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> All applications are checked every minute in real-time mode. This field is kept for historical/reporting purposes.
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Timeout (seconds)</label>
                <input type="number" name="timeout" value="{{ $application->timeout }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex gap-3 pt-4 border-t">
                <button type="submit" class="btn-primary">Update Application</button>
                <a href="{{ route('applications') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
