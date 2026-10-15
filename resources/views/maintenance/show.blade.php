@extends('layouts.app')

@section('page_title', 'Maintenance Window')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Maintenance Window</h1>
            <p class="text-gray-600 mt-1">Application: <span class="font-semibold">{{ $maintenance->application->name ?? 'N/A' }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('maintenance.edit', $maintenance->id) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('maintenance.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Maintenance Status</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $maintenance->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : ($maintenance->status === 'active' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                {{ ucfirst($maintenance->status ?? 'unknown') }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Title</p>
                <p class="text-gray-800 font-semibold">{{ $maintenance->title ?? '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Alerts Disabled</p>
                <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium {{ $maintenance->disable_alerts ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    <i class="fas {{ $maintenance->disable_alerts ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                    {{ $maintenance->disable_alerts ? 'Yes' : 'No' }}
                </span>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Start Time</p>
                <p class="text-gray-800">{{ $maintenance->start_time ? (is_string($maintenance->start_time) ? \Carbon\Carbon::parse($maintenance->start_time)->format('M d, Y H:i') : $maintenance->start_time->format('M d, Y H:i')) : '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">End Time</p>
                <p class="text-gray-800">{{ $maintenance->end_time ? (is_string($maintenance->end_time) ? \Carbon\Carbon::parse($maintenance->end_time)->format('M d, Y H:i') : $maintenance->end_time->format('M d, Y H:i')) : '—' }}</p>
            </div>
        </div>
    </div>

    <!-- Description Card -->
    @if($maintenance->description)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Description</h3>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-gray-700">{{ $maintenance->description }}</p>
            </div>
        </div>
    @endif

    <!-- Timeline Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Timeline</h3>
        <div class="space-y-4">
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                    <div class="w-1 h-12 bg-gray-300 mt-2"></div>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Scheduled Start</p>
                    <p class="text-gray-600 text-sm">{{ $maintenance->start_time ? (is_string($maintenance->start_time) ? \Carbon\Carbon::parse($maintenance->start_time)->format('M d, Y H:i') : $maintenance->start_time->format('M d, Y H:i')) : '—' }}</p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-4 h-4 {{ $maintenance->status === 'completed' ? 'bg-green-500' : 'bg-yellow-500' }} rounded-full"></div>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $maintenance->status === 'completed' ? 'Completed' : 'Scheduled End' }}</p>
                    <p class="text-gray-600 text-sm">{{ $maintenance->end_time ? (is_string($maintenance->end_time) ? \Carbon\Carbon::parse($maintenance->end_time)->format('M d, Y H:i') : $maintenance->end_time->format('M d, Y H:i')) : '—' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
