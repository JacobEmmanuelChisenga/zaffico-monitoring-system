@extends('layouts.app')

@section('page_title', 'Monitoring Checks')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Monitoring Checks</h1>
            <p class="text-gray-600 mt-1">View all health check records grouped by intervals</p>
        </div>
        <a href="{{ route('checks.create') }}" class="btn-primary flex items-center gap-2">
            <i class="fas fa-plus"></i> Trigger Check
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('checks.index') }}" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Application</label>
                <select name="application_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Applications</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}" {{ request('application_id') == $app->id ? 'selected' : '' }}>
                            {{ $app->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="up" {{ request('status') == 'up' ? 'selected' : '' }}>Up</option>
                    <option value="down" {{ request('status') == 'down' ? 'selected' : '' }}>Down</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn-primary px-4 py-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            @if(request('application_id') || request('status'))
                <div>
                    <a href="{{ route('checks.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Checks Grouped by Intervals -->
    @if(count($groupedChecks) > 0)
        <div class="space-y-4">
            @foreach($groupedChecks as $intervalKey => $group)
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">
                                    <i class="fas fa-clock text-blue-600"></i>
                                    Interval: {{ $group['interval_start']->format('M d, Y H:i') }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    Check Interval: {{ $group['interval'] }} minutes • 
                                    {{ count($group['checks']) }} check(s) in this interval
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                {{ $group['interval_start']->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Response Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Checked At</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($group['checks'] as $check)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-800">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-server text-gray-400"></i>
                                                {{ $check->application->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                                {{ $check->status === 'up' ? 'bg-green-100 text-green-800' : ($check->status === 'down' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                <i class="fas {{ $check->status === 'up' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                                {{ ucfirst($check->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span class="font-medium">{{ $check->response_time }}</span> ms
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            @if($check->status_code)
                                                <span class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{{ $check->status_code }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            @if($check->checked_at)
                                                <span title="{{ $check->checked_at->format('M d, Y H:i:s') }} ({{ config('app.timezone') }})">
                                                    {{ $check->checked_at->format('H:i:s') }}
                                                </span>
                                                <span class="text-gray-400 text-xs ml-1">({{ $check->checked_at->diffForHumans() }})</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm flex gap-2">
                                            <a href="{{ route('checks.show', $check->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form action="{{ route('checks.destroy', $check->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this check?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $checks->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-600 text-lg font-medium">No checks recorded yet</p>
            <p class="text-gray-500 text-sm mt-2">Checks will appear here once monitoring starts</p>
        </div>
    @endif
</div>
@endsection
