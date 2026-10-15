@extends('layouts.app')

@section('page_title', 'Edit Downtime Incident')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Downtime Incident</h1>
            <p class="text-gray-600 mt-1">Update incident details and resolution status</p>
        </div>
        <a href="{{ route('incidents.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold mb-2">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('incidents.update', $incident->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Application *</label>
                    <select name="application_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        @foreach($applications as $app)
                            <option value="{{ $app->id }}" {{ $incident->application_id == $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Severity *</label>
                    <select name="severity" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="critical" {{ $incident->severity == 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ $incident->severity == 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ $incident->severity == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ $incident->severity == 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Started At *</label>
                    <input type="datetime-local" name="started_at" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $incident->started_at?->format('Y-m-d\TH:i') }}" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Resolved At</label>
                    <input type="datetime-local" name="resolved_at" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $incident->resolved_at?->format('Y-m-d\TH:i') }}">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Error Message</label>
                <textarea name="error_message" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4">{{ $incident->error_message }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-save"></i> Update Incident
                </button>
                <a href="{{ route('incidents.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
