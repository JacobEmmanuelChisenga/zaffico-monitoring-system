<?php

use App\Http\Controllers\ApplicationsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\MonitoringCheckController;
use App\Http\Controllers\DowntimeIncidentController;
use App\Http\Controllers\AlertRuleController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\UptimeReportController;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/api/status', [WelcomeController::class, 'apiStatus'])->name('api.status');

// Dashboard API
Route::get('/api/dashboard/status', [DashboardController::class, 'apiStatus'])->name('api.dashboard.status')->middleware('auth');

// Applications API
Route::get('/api/applications', [ApplicationsController::class, 'apiIndex'])->name('api.applications.index')->middleware('auth');
Route::get('/api/applications/{application}', [ApplicationsController::class, 'apiShow'])->name('api.applications.show')->middleware('auth');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('applications', [ApplicationsController::class, 'index'])->name('applications');
Route::get('applications/create', [ApplicationsController::class, 'create'])->name('applications.create');
Route::post('applications/', [ApplicationsController::class, 'store'])->name('applications.store');
Route::get('applications/{application}', [ApplicationsController::class, 'show'])->name('applications.show');
Route::get('applications/{application}/edit', [ApplicationsController::class, 'edit'])->name('applications.edit');
Route::put('applications/{application}', [ApplicationsController::class, 'update'])->name('applications.update');
Route::delete('applications/{application}', [ApplicationsController::class, 'destroy'])->name('applications.destroy');

Route::get('checks', [MonitoringCheckController::class, 'index'])->name('checks.index');
Route::get('checks/create', [MonitoringCheckController::class, 'create'])->name('checks.create');
Route::post('checks', [MonitoringCheckController::class, 'store'])->name('checks.store');
Route::get('checks/{check}', [MonitoringCheckController::class, 'show'])->name('checks.show');
Route::get('checks/{check}/edit', [MonitoringCheckController::class, 'edit'])->name('checks.edit');
Route::put('checks/{check}', [MonitoringCheckController::class, 'update'])->name('checks.update');
Route::delete('checks/{check}', [MonitoringCheckController::class, 'destroy'])->name('checks.destroy');

Route::get('incidents', [DowntimeIncidentController::class, 'index'])->name('incidents.index');
Route::get('incidents/create', [DowntimeIncidentController::class, 'create'])->name('incidents.create');
Route::post('incidents/', [DowntimeIncidentController::class, 'store'])->name('incidents.store');
Route::get('incidents/{incident}', [DowntimeIncidentController::class, 'show'])->name('incidents.show');
Route::get('incidents/{incident}/edit', [DowntimeIncidentController::class, 'edit'])->name('incidents.edit');
Route::put('incidents/{incident}', [DowntimeIncidentController::class, 'update'])->name('incidents.update');
Route::delete('incidents/{incident}', [DowntimeIncidentController::class, 'destroy'])->name('incidents.destroy');

Route::get('alerts', [AlertRuleController ::class, 'index'])->name('alerts');
Route::get('alerts/create', [AlertRuleController ::class, 'create'])->name('alerts.create');
Route::post('alerts/', [AlertRuleController ::class, 'store'])->name('alerts.store');
Route::get('alerts/{alert}', [AlertRuleController ::class, 'show'])->name('alerts.show');
Route::get('alerts/{alert}/edit', [AlertRuleController::class, 'edit'])->name('alerts.edit');
Route::put('alerts/{alert}', [AlertRuleController ::class, 'update'])->name('alerts.update');
Route::delete('alerts/{alert}', [AlertRuleController ::class, 'destroy'])->name('alerts.destroy');


Route::get('maintenance', [MaintenanceWindowController::class, 'index'])->name('maintenance.index');
Route::get('maintenance/create', [MaintenanceWindowController ::class, 'create'])->name('maintenance.create');
Route::post('maintenance/', [MaintenanceWindowController ::class, 'store'])->name('maintenance.store');
Route::get('maintenance/{maintenance}', [MaintenanceWindowController ::class, 'show'])->name('maintenance.show');
Route::get('maintenance/{maintenance}/edit', [MaintenanceWindowController::class, 'edit'])->name('maintenance.edit');
Route::put('maintenance/{maintenance}', [MaintenanceWindowController ::class, 'update'])->name('maintenance.update');
Route::delete('maintenance/{maintenance}', [MaintenanceWindowController ::class, 'destroy'])->name('maintenance.destroy');

Route::get('uptime-reports', [UptimeReportController::class, 'index'])->name('uptime.index');
Route::get('uptime-reports/generate', [UptimeReportController::class, 'generate'])->name('uptime.generate');
Route::get('uptime-reports/download-pdf', [UptimeReportController::class, 'downloadPdf'])->name('uptime.download-pdf');
require __DIR__.'/auth.php';
