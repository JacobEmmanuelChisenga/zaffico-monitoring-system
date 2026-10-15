<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DowntimeIncident;
use App\Models\Applications;
use App\Models\User;
use Carbon\Carbon;

class DowntimeIncidentSeeder extends Seeder
{
    public function run()
    {
        $applications = Applications::all();
        $resolver = User::where('role', 'manager')->first();

        foreach ($applications as $app) {
            DowntimeIncident::create([
                'application_id' => $app->id,
                'started_at' => Carbon::now()->subHours(rand(1, 24)),
                'resolved_at' => Carbon::now()->subHours(rand(0, 12)),
                'duration' => rand(5, 120),
                'severity' => ['critical','high','medium','low'][rand(0,3)],
                'error_message' => 'Sample downtime incident',
                'resolution_notes' => 'Resolved automatically',
                'resolved_by' => $resolver->id,
                'notification_sent' => true
            ]);
        }
    }
}
