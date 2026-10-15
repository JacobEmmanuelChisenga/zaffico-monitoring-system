@extends('layouts.app')

@section('page_title', 'Alert Rules')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Alert Rules</h1>
            <p class="text-gray-600 mt-1">Configure alert triggers and notifications</p>
        </div>
        <a href="{{ route('alerts.create') }}" class="btn-primary flex items-center gap-2">
            <i class="fas fa-plus"></i> Create Rule
        </a>
    </div>

    <!-- Rules Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Trigger Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Threshold</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Channels</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rules as $rule)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $rule->application->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($rule->trigger_type ?? 'unknown') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $rule->threshold_value ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($rule->notification_channels)
                                    @foreach((array)$rule->notification_channels as $channel)
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded mr-1">{{ ucfirst($channel) }}</span>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm flex gap-2">
                                <a href="{{ route('alerts.show', $rule->id) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                <a href="{{ route('alerts.edit', $rule->id) }}" class="text-gray-600 hover:text-gray-800">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-bell text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No alert rules configured</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
