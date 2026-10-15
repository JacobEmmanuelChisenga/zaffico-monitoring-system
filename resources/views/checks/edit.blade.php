@extends('layouts.app')

@section('page_title', 'Monitoring Check Details')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="{{ route('checks.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Checks
    </a>

    <!-- Check Details Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <h2 class="text-2xl font-bold text-gray-800">Monitoring Check Details</h2>
            <p class="text-gray-600 text-sm mt-1">Application: <span class="font-semibold">{{ $check->application->name ?? 'N/A' }}</span></p>
        </div>

        <!-- Status Badge -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $check->status === 'up' ? 'bg-green-100 text-green-800' : ($check->status === 'down' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($check->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Response Time</p>
                    <p class="text-lg font-bold text-gray-800">{{ $check->response_time ?? '—' }} <span class="text-sm">ms</span></p>
                </div>
            </div>
        </div>

        <!-- Check Details -->
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Status Code</p>
                    <p class="text-gray-800 text-lg font-mono bg-gray-50 px-3 py-2 rounded">{{ $check->status_code ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Checked At</p>
                    <p class="text-gray-800">{{ $check->checked_at?->format('M d, Y H:i:s') ?? '—' }}</p>
                </div>
            </div>

            @if($check->error_message)
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Error Message</p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm">
                        {{ $check->error_message }}
                    </div>
                </div>
            @endif

            @if($check->response_body)
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Response Body</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm font-mono overflow-auto max-h-48">
                        {{ $check->response_body }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Timestamps -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <p class="text-gray-600 text-xs">
                <i class="fas fa-clock"></i> Created: {{ $check->created_at?->format('M d, Y H:i') ?? '—' }} | 
                Updated: {{ $check->updated_at?->format('M d, Y H:i') ?? '—' }}
            </p>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
            <form action="{{ route('checks.destroy', $check->id) }}" method="POST" onsubmit="return confirm('Delete this check?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger flex items-center gap-2">
                    <i class="fas fa-trash"></i> Delete Check
                </button>
            </form>
            <a href="{{ route('checks.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-times"></i> Close
            </a>
        </div>
    </div>
</div>
@endsection
