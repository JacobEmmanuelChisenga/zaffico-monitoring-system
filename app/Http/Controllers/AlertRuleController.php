<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlertRule;
use App\Models\Applications;
use App\Models\MonitoringCheck;

class AlertRuleController extends Controller
{
    // List all alert rules
    public function index()
    {
        $rules = AlertRule::with('application', 'monitoringCheck')->latest()->get();
        return view('alerts.index', compact('rules'));
    }

    // Show form to create a new alert rule
    public function create()
    {
        $applications = Applications::all();
        $checks = MonitoringCheck::all();
        return view('alerts.create', compact('applications', 'checks'));
    }

    // Store new alert rule
    public function store(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'monitoring_check_id' => 'nullable|exists:monitoring_checks,id',
            'condition' => 'required|string',
            'threshold' => 'required|numeric|min:1',
            'channel' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        AlertRule::create($request->all());

        return redirect()->route('alerts.index')->with('success', 'Alert rule created successfully.');
    }

    // Show single alert rule
    public function show(AlertRule $alert)
    {
        return view('alerts.show', compact('alert'));
    }

    // Edit alert rule
    public function edit(AlertRule $alert)
    {
        $applications = Applications::all();
        $checks = MonitoringCheck::all();
        return view('alerts.edit', compact('alert', 'applications', 'checks'));
    }

    // Update alert rule
    public function update(Request $request, AlertRule $alert)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'monitoring_check_id' => 'nullable|exists:monitoring_checks,id',
            'condition' => 'required|string',
            'threshold' => 'required|numeric|min:1',
            'channel' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $alert->update($request->all());

        return redirect()->route('alerts.index')->with('success', 'Alert rule updated successfully.');
    }

    // Delete alert rule
    public function destroy(AlertRule $alert)
    {
        $alert->delete();
        return redirect()->route('alerts.index')->with('success', 'Alert rule deleted successfully.');
    }
}
