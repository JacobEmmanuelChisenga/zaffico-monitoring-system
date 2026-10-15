@extends('layouts.app')

@section('page_title', 'Downtime Incidents')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Downtime Incidents</h1>
            <p class="text-gray-600 mt-1">Track and manage application outages</p>
        </div>
        <a href="{{ route('incidents.create') }}" class="btn-primary flex items-center gap-2">
            <i class="fas fa-plus"></i> Create Incident
        </a>
    </div>

    <!-- Incidents Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Started</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($incidents as $incident)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $incident->application->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $incident->started_at?->format('M d, H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $incident->duration ? $incident->duration . ' min' : '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $incident->severity === 'critical' ? 'bg-red-100 text-red-800' : ($incident->severity === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($incident->severity ?? 'unknown') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $incident->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $incident->resolved_at ? 'Resolved' : 'Ongoing' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm flex gap-2">
                                <a href="{{ route('incidents.show', $incident->id) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                <a href="{{ route('incidents.edit', $incident->id) }}" class="text-gray-600 hover:text-gray-800">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-check-circle text-green-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No incidents recorded. Great!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
