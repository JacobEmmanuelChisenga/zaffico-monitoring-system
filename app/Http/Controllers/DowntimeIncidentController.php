<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DowntimeIncident;
use App\Models\Applications;
use App\Models\MonitoringCheck;

class DowntimeIncidentController extends Controller
{
    // List all incidents
    public function index()
    {
        $incidents = DowntimeIncident::with('application')->latest()->get();
        return view('incidents.index', compact('incidents'));
    }

    // Show form to create a new incident
    public function create()
    {
        $applications = Applications::all();
        $checks = MonitoringCheck::all();
        return view('incidents.create', compact('applications', 'checks'));
    }

    // Store new incident
    public function store(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'started_at' => 'required|date',
            'resolved_at' => 'nullable|date',
            'severity' => 'required|in:critical,high,medium,low',
            'error_message' => 'nullable|string',
        ]);

        DowntimeIncident::create($request->all());

        return redirect()->route('incidents.index')->with('success', 'Incident recorded successfully.');
    }

    // Show single incident details
    public function show(DowntimeIncident $incident)
    {
        return view('incidents.show', compact('incident'));
    }

    // Show form to edit an incident
    public function edit(DowntimeIncident $incident)
    {
        $applications = Applications::all();
        $checks = MonitoringCheck::all();
        return view('incidents.edit', compact('incident', 'applications', 'checks'));
    }

    // Update incident
    public function update(Request $request, DowntimeIncident $incident)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'started_at' => 'required|date',
            'resolved_at' => 'nullable|date',
            'severity' => 'required|in:critical,high,medium,low',
            'error_message' => 'nullable|string',
        ]);

        $data = $request->all();
        
        // Calculate duration if resolved_at is being set and wasn't previously set
        if (isset($data['resolved_at']) && $data['resolved_at'] && !$incident->resolved_at) {
            $resolvedAt = \Carbon\Carbon::parse($data['resolved_at']);
            $data['duration'] = (int) round($incident->started_at->diffInMinutes($resolvedAt));
        } elseif (isset($data['resolved_at']) && $data['resolved_at'] && $incident->resolved_at) {
            // If updating resolved_at, recalculate duration
            $resolvedAt = \Carbon\Carbon::parse($data['resolved_at']);
            $data['duration'] = (int) round($incident->started_at->diffInMinutes($resolvedAt));
        }

        $incident->update($data);

        return redirect()->route('incidents.index')->with('success', 'Incident updated successfully.');
    }

    // Delete incident
    public function destroy(DowntimeIncident $incident)
    {
        $incident->delete();
        return redirect()->route('incidents.index')->with('success', 'Incident deleted successfully.');
    }
}
