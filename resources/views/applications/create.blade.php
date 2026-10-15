@extends('layouts.app')

@section('page_title', 'Create Application')

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
            <h2 class="text-2xl font-bold text-gray-800">Add New Application</h2>
            <p class="text-gray-600 text-sm mt-1">Configure a new application to monitor</p>
        </div>

        <!-- Errors -->
        @if ($errors->any())
            <div class="m-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold mb-2 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
                </p>
                <ul class="text-red-700 text-sm space-y-1 ml-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('applications.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Application Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Application Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., DMS, HRS, Sage">
                @error('name')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- IP Address -->
            <div>
                <label for="ip_address" class="block text-sm font-semibold text-gray-700 mb-2">IP Address *</label>
                <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., 192.168.1.10">
                @error('ip_address')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Port -->
            <div>
                <label for="port" class="block text-sm font-semibold text-gray-700 mb-2">Port <span class="text-gray-500 text-xs">(Optional)</span></label>
                <input type="number" id="port" name="port" value="{{ old('port') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., 80, 443, 8080">
                <p class="text-gray-500 text-xs mt-1">Leave empty for standard ports (80 for HTTP, 443 for HTTPS)</p>
                @error('port')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Protocol -->
            <div>
                <label for="protocol" class="block text-sm font-semibold text-gray-700 mb-2">Protocol *</label>
                <select id="protocol" name="protocol" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">-- Select Protocol --</option>
                    <option value="http" {{ old('protocol') === 'http' ? 'selected' : '' }}>HTTP</option>
                    <option value="https" {{ old('protocol') === 'https' ? 'selected' : '' }}>HTTPS</option>
                    <option value="ping" {{ old('protocol') === 'ping' ? 'selected' : '' }}>Ping</option>
                    <option value="tcp" {{ old('protocol') === 'tcp' ? 'selected' : '' }}>TCP</option>
                </select>
                @error('protocol')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Endpoint (for HTTP/HTTPS) -->
            <div>
                <label for="endpoint" class="block text-sm font-semibold text-gray-700 mb-2">Endpoint <span class="text-gray-500 text-xs">(Optional)</span></label>
                <input type="text" id="endpoint" name="endpoint" value="{{ old('endpoint') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., /health, /api/status, /index.html">
                <p class="text-gray-500 text-xs mt-1">For HTTP/HTTPS protocols only. Defaults to root (/) if empty</p>
                @error('endpoint')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Check Interval (Informational - Real-time monitoring checks all apps every minute) -->
            <div>
                <label for="check_interval" class="block text-sm font-semibold text-gray-700 mb-2">
                    Check Interval (minutes) 
                    <span class="text-xs text-gray-500 font-normal">(Informational only - Real-time mode checks all apps every minute)</span>
                </label>
                <input type="number" id="check_interval" name="check_interval" value="{{ old('check_interval', 1) }}" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-gray-50"
                    placeholder="1" readonly>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> All applications are checked every minute in real-time mode. This field is kept for historical/reporting purposes.
                </p>
                @error('check_interval')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Timeout -->
            <div>
                <label for="timeout" class="block text-sm font-semibold text-gray-700 mb-2">Timeout (seconds)</label>
                <input type="number" id="timeout" name="timeout" value="{{ old('timeout', 30) }}" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="30">
                @error('timeout')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="Add notes about this application...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-save"></i> Create Application
                </button>
                <a href="{{ route('applications') }}" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
