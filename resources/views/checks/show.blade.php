@extends('layouts.app')

@section('page_title', 'Monitoring Check')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Monitoring Check</h1>
            <p class="text-gray-600 mt-1">Application: <span class="font-semibold">{{ $check->application->name ?? 'N/A' }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('checks.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Check Details -->
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Check Details</h3>
                <p class="text-gray-600 text-sm mt-1">Recorded at: {{ $check->checked_at?->format('M d, Y H:i:s') ?? '—' }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $check->status === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                <i class="fas {{ $check->status === 'up' ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                {{ strtoupper($check->status ?? 'UNKNOWN') }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Response Time</p>
                <p class="text-gray-800 font-bold">{{ $check->response_time !== null ? $check->response_time . ' ms' : '—' }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Status Code</p>
                <p class="text-gray-800">{{ $check->status_code ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Recorded ({{ config('app.timezone') }})</p>
                <p class="text-gray-800 font-bold">
                    {{ $check->checked_at?->timezone(config('app.timezone'))->format('M d, Y H:i:s') ?? '—' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Application Endpoint</p>
                <p class="text-gray-800 font-mono bg-gray-50 px-3 py-2 rounded">
                    {{ $check->application?->protocol ? strtoupper($check->application->protocol) . '://' : '' }}
                    {{ $check->application->ip_address ?? '—' }}
                    @if($check->application?->port)
                        :{{ $check->application->port }}
                    @endif
                    @if($check->application?->endpoint)
                        /{{ ltrim($check->application->endpoint, '/') }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Response Body</p>
                <pre class="text-xs text-gray-800 bg-gray-50 px-3 py-2 rounded overflow-x-auto">{{ $check->response_body ?? '—' }}</pre>
            </div>
        </div>

        <div>
            <p class="text-gray-600 text-sm font-semibold mb-1">Error Message</p>
            <p class="text-gray-800 bg-red-50 text-red-800 px-3 py-2 rounded">{{ $check->error_message ?? '—' }}</p>
        </div>
    </div>

    <!-- Timestamps Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Timestamps</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Created</p>
                <p class="text-gray-800">{{ $check->created_at?->format('M d, Y H:i') ?? '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Updated</p>
                <p class="text-gray-800">{{ $check->updated_at?->format('M d, Y H:i') ?? '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Last Checked</p>
                <p class="text-gray-800">{{ $check->checked_at?->format('M d, Y H:i') ?? 'Never' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
