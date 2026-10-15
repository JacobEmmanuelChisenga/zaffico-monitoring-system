@extends('layouts.app')

@section('page_title', 'Create Monitoring Check')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="{{ route('checks.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-2 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Checks
    </a>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Trigger Health Check</h2>
            <p class="text-gray-600 text-sm mt-1">Run a manual health check for an application</p>
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
        <form action="{{ route('checks.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Application -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Application *</label>
                <select name="application_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">-- Select Application --</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}" {{ old('application_id') == $app->id ? 'selected' : '' }}>{{ $app->name }} ({{ $app->ip_address }})</option>
                    @endforeach
                </select>
                @error('application_id')
                    <p class="text-red-600 text-sm mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-play"></i> Run Health Check
                </button>
                <a href="{{ route('checks.index') }}" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
