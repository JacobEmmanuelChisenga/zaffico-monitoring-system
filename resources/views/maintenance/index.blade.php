@extends('layouts.app')

@section('page_title', 'Maintenance Windows')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Maintenance Windows</h1>
            <p class="text-gray-600 mt-1">Schedule planned maintenance and suppress alerts</p>
        </div>
        <a href="{{ route('maintenance.create') }}" class="btn-primary flex items-center gap-2">
            <i class="fas fa-plus"></i> Schedule Window
        </a>
    </div>

    <!-- Maintenance Windows Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">End Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($windows as $window)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $window->application->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $window->title ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $window->start_time?->format('M d, H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $window->end_time?->format('M d, H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $window->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : ($window->status === 'active' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ ucfirst($window->status ?? 'scheduled') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm flex gap-2">
                                <a href="{{ route('maintenance.show', $window->id) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                <a href="{{ route('maintenance.edit', $window->id) }}" class="text-gray-600 hover:text-gray-800">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-wrench text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No maintenance windows scheduled</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
