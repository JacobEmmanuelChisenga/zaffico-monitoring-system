@extends('layouts.app')

@section('content')
<h1 class="mb-4">Edit Maintenance Window</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('maintenance.update', $maintenance->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Application</label>
        <select name="application_id" class="form-control" required>
            @foreach($applications as $app)
                <option value="{{ $app->id }}" {{ $maintenance->application_id == $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="{{ $maintenance->title }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Start At</label>
        <input type="datetime-local" name="start_at" class="form-control" value="{{ $maintenance->start_time ? date('Y-m-d\TH:i', strtotime($maintenance->start_time)) : '' }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">End At</label>
        <input type="datetime-local" name="end_at" class="form-control" value="{{ $maintenance->end_time ? date('Y-m-d\TH:i', strtotime($maintenance->end_time)) : '' }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control" required>
            <option value="scheduled" {{ $maintenance->status == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="completed" {{ $maintenance->status == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ $maintenance->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ $maintenance->description }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">Update Maintenance Window</button>
</form>
@endsection
