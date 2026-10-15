@extends('layouts.app')

@section('page_title', 'Schedule Maintenance Window')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="{{ route('maintenance.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Maintenance Windows
    </a>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Schedule Maintenance Window</h2>
            <p class="text-gray-600 text-sm mt-1">Schedule planned maintenance and suppress alerts</p>
        </div>

        <!-- Errors -->
        @if ($errors->any())
            <div class="m-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold mb-2">
                    <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
                </p>
                <ul class="text-red-700 text-sm space-y-1 ml-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('maintenance.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Application -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Application *</label>
                <select name="application_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">-- Select Application --</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}" {{ old('application_id') == $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                    @endforeach
                </select>
                @error('application_id')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="e.g., Database Migration, Server Upgrade">
                @error('title')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Start Time -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time *</label>
                <input type="datetime-local" name="start_at" value="{{ old('start_at') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                @error('start_at')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- End Time -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Time *</label>
                <input type="datetime-local" name="end_at" value="{{ old('end_at') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                @error('end_at')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                <select name="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">-- Select Status --</option>
                    <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Disable Alerts -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="disable_alerts" value="1" {{ old('disable_alerts', true) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 rounded">
                    <span class="ml-3 text-gray-700 font-semibold">Disable Alerts During Maintenance</span>
                </label>
                <p class="text-gray-500 text-xs mt-1">Alerts will be suppressed during this maintenance window</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="Add details about the maintenance...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-calendar-check"></i> Schedule Window
                </button>
                <a href="{{ route('maintenance.index') }}" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
