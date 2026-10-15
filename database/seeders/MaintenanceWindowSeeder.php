<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceWindow;
use App\Models\Applications;
use App\Models\User;
use Carbon\Carbon;

class MaintenanceWindowSeeder extends Seeder
{
    public function run()
    {
        $creator = User::where('role', 'admin')->first();
        $applications = Applications::all();

        foreach ($applications as $app) {
            MaintenanceWindow::create([
                'application_id' => $app->id,
                'title' => 'Routine Maintenance',
                'description' => 'Scheduled maintenance window for testing',
                'start_time' => Carbon::now()->addDays(rand(1,3)),
                'end_time' => Carbon::now()->addDays(rand(1,3))->addHours(2),
                'disable_alerts' => true,
                'status' => 'scheduled',
                'created_by' => $creator->id
            ]);
        }
    }
}
