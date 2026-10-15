@extends('layouts.app')

@section('page_title', 'Incident Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Downtime Incident</h1>
            <p class="text-gray-600 mt-1">Application: <span class="font-semibold">{{ $incident->application->name ?? 'N/A' }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('incidents.edit', $incident->id) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('incidents.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Incident Status</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $incident->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $incident->resolved_at ? 'Resolved' : 'Ongoing' }}
            </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Severity</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $incident->severity === 'critical' ? 'bg-red-100 text-red-800' : ($incident->severity === 'high' ? 'bg-orange-100 text-orange-800' : ($incident->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                    {{ ucfirst($incident->severity ?? 'unknown') }}
                </span>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Duration</p>
                <p class="text-gray-800">{{ $incident->duration ? $incident->duration . ' minutes' : '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Started At</p>
                <p class="text-gray-800">{{ is_string($incident->started_at) ? \Carbon\Carbon::parse($incident->started_at)->format('M d, Y H:i:s') : $incident->started_at->format('M d, Y H:i:s') }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Resolved At</p>
                <p class="text-gray-800">{{ $incident->resolved_at ? (is_string($incident->resolved_at) ? \Carbon\Carbon::parse($incident->resolved_at)->format('M d, Y H:i:s') : $incident->resolved_at->format('M d, Y H:i:s')) : '—' }}</p>
            </div>
        </div>
    </div>

    <!-- Details Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Incident Details</h3>
        <div class="space-y-4">
            @if($incident->error_message)
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Error Message</p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-700 text-sm font-mono">{{ $incident->error_message }}</p>
                    </div>
                </div>
            @endif

            @if($incident->resolution_notes)
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Resolution Notes</p>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-green-700">{{ $incident->resolution_notes }}</p>
                    </div>
                </div>
            @endif

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-2">Resolved By</p>
                <p class="text-gray-800">{{ $incident->resolvedBy->name ?? 'Not resolved yet' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-2">Notification Sent</p>
                <p class="text-gray-800">
                    <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium {{ $incident->notification_sent ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        <i class="fas {{ $incident->notification_sent ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                        {{ $incident->notification_sent ? 'Yes' : 'No' }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Timeline Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Timeline</h3>
        <div class="space-y-4">
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                    <div class="w-1 h-12 bg-gray-300 mt-2"></div>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Incident Started</p>
                    <p class="text-gray-600 text-sm">{{ is_string($incident->started_at) ? \Carbon\Carbon::parse($incident->started_at)->format('M d, Y H:i') : $incident->started_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            @if($incident->resolved_at)
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Incident Resolved</p>
                        <p class="text-gray-600 text-sm">{{ is_string($incident->resolved_at) ? \Carbon\Carbon::parse($incident->resolved_at)->format('M d, Y H:i') : $incident->resolved_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            @else
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-4 h-4 bg-yellow-500 rounded-full animate-pulse"></div>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Ongoing</p>
                        <p class="text-gray-600 text-sm">Waiting for recovery...</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
