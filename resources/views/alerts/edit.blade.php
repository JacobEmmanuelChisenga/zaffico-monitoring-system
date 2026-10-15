@extends('layouts.app')

@section('page_title', 'Edit Alert Rule')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="{{ route('alerts') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Alert Rules
    </a>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Edit Alert Rule</h2>
            <p class="text-gray-600 text-sm mt-1">Update alert rule configuration</p>
        </div>

        <!-- Errors -->
        @if ($errors->any())
            <div class="m-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-semibold mb-2"><i class="fas fa-exclamation-circle"></i> Please fix errors</p>
                <ul class="text-red-700 text-sm space-y-1 ml-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('alerts.update', $alert->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Application -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Application *</label>
                <select name="application_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}" {{ $alert->application_id == $app->id ? 'selected' : '' }}>
                            {{ $app->name }}
                        </option>
                    @endforeach
                </select>
                @error('application_id')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Trigger Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Trigger Type *</label>
                <select name="trigger_type" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="consecutive_failures" {{ $alert->trigger_type == 'consecutive_failures' ? 'selected' : '' }}>Consecutive Failures</option>
                    <option value="response_time" {{ $alert->trigger_type == 'response_time' ? 'selected' : '' }}>Response Time Threshold</option>
                    <option value="status_code" {{ $alert->trigger_type == 'status_code' ? 'selected' : '' }}>Bad Status Code</option>
                </select>
                @error('trigger_type')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Threshold Value -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Threshold Value</label>
                <input type="number" name="threshold_value" value="{{ $alert->threshold_value }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., 500 for 500ms response time">
                @error('threshold_value')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Consecutive Failures -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Consecutive Failures</label>
                <input type="number" name="consecutive_failures" value="{{ $alert->consecutive_failures }}" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-gray-500 text-xs mt-1">Number of consecutive failures before triggering alert</p>
                @error('consecutive_failures')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Notification Channels -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Notification Channels *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="notification_channels[]" value="email"
                            {{ (is_array($alert->notification_channels) && in_array('email', $alert->notification_channels)) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-3 text-gray-700">Email</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="notification_channels[]" value="sms"
                            {{ (is_array($alert->notification_channels) && in_array('sms', $alert->notification_channels)) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-3 text-gray-700">SMS</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="notification_channels[]" value="slack"
                            {{ (is_array($alert->notification_channels) && in_array('slack', $alert->notification_channels)) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-3 text-gray-700">Slack</span>
                    </label>
                </div>
                @error('notification_channels')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Recipients -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Recipients *</label>
                <textarea name="recipients" rows="3" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter email addresses or phone numbers (one per line)">{{ is_array($alert->recipients) ? implode("\n", $alert->recipients) : $alert->recipients }}</textarea>
                <p class="text-gray-500 text-xs mt-1">Enter one recipient per line</p>
                @error('recipients')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ $alert->is_active ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 rounded">
                    <span class="ml-3 text-gray-700 font-semibold">Activate This Rule</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-save"></i> Update Alert Rule
                </button>
                <a href="{{ route('alerts') }}" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
