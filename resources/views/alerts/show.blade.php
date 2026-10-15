@extends('layouts.app')

@section('page_title', 'Alert Rule')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Alert Rule</h1>
            <p class="text-gray-600 mt-1">Application: <span class="font-semibold">{{ $alert->application->name ?? 'N/A' }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('alerts.edit', $alert->id) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('alerts') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Details Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6">Rule Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Trigger Type</p>
                <p class="text-gray-800">{{ ucfirst($alert->trigger_type ?? 'unknown') }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Threshold Value</p>
                <p class="text-gray-800">{{ $alert->threshold_value ?? '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Consecutive Failures</p>
                <p class="text-gray-800">{{ $alert->consecutive_failures ?? '—' }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-semibold mb-1">Status</p>
                <p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $alert->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $alert->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
            </div>

            <div class="md:col-span-2">
                <p class="text-gray-600 text-sm font-semibold mb-1">Notification Channels</p>
                <div class="flex gap-2 flex-wrap">
                    @if($alert->notification_channels)
                        @foreach((array)$alert->notification_channels as $channel)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-{{ $channel === 'email' ? 'envelope' : 'bell' }} mr-2"></i>
                                {{ ucfirst($channel) }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-gray-600">—</p>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <p class="text-gray-600 text-sm font-semibold mb-1">Recipients</p>
                <div class="flex gap-2 flex-wrap">
                    @if($alert->recipients)
                        @foreach((array)$alert->recipients as $recipient)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-user mr-2"></i>
                                {{ $recipient }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-gray-600">—</p>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <p class="text-gray-600 text-sm font-semibold mb-1">Timestamps</p>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Created</p>
                        <p class="text-gray-800">{{ $alert->created_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Updated</p>
                        <p class="text-gray-800">{{ $alert->updated_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
