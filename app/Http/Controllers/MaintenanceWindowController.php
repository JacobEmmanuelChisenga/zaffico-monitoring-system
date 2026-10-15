<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceWindow;
use App\Models\Applications;

class MaintenanceWindowController extends Controller
{
    // List all maintenance windows
    public function index()
    {
        $windows = MaintenanceWindow::with('application')->latest()->get();
        return view('maintenance.index', compact('windows'));
    }

    // Show form to create a new window
    public function create()
    {
        $applications = Applications::all();
        return view('maintenance.create', compact('applications'));
    }

    // Store new maintenance window
    public function store(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'title' => 'required|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'description' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled',
            'disable_alerts' => 'nullable|boolean',
        ]);

        // Map form fields to database columns
        $data = $request->only(['application_id', 'title', 'description', 'status', 'disable_alerts']);
        $data['start_time'] = $request->input('start_at');
        $data['end_time'] = $request->input('end_at');
        $data['created_by'] = auth()->id();

        MaintenanceWindow::create($data);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance window created successfully.');
    }

    // Show single maintenance window
    public function show(MaintenanceWindow $maintenance)
    {
        return view('maintenance.show', compact('maintenance'));
    }

    // Edit maintenance window
    public function edit(MaintenanceWindow $maintenance)
    {
        $applications = Applications::all();
        return view('maintenance.edit', compact('maintenance', 'applications'));
    }

    // Update maintenance window
    public function update(Request $request, MaintenanceWindow $maintenance)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'title' => 'required|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'description' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled',
            'disable_alerts' => 'nullable|boolean',
        ]);

        // Map form fields to database columns
        $data = $request->only(['application_id', 'title', 'description', 'status', 'disable_alerts']);
        $data['start_time'] = $request->input('start_at');
        $data['end_time'] = $request->input('end_at');

        $maintenance->update($data);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance window updated successfully.');
    }

    // Delete maintenance window
    public function destroy(MaintenanceWindow $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success', 'Maintenance window deleted successfully.');
    }
}
