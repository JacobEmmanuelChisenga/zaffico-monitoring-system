<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MonitoringCheck;
use App\Models\Applications;
use Carbon\Carbon;

class MonitoringCheckSeeder extends Seeder
{
    public function run()
    {
        $applications = Applications::all();

        foreach ($applications as $app) {
            for ($i = 0; $i < 10; $i++) {
                MonitoringCheck::create([
                    'application_id' => $app->id,
                    'status' => ['up', 'down', 'timeout'][rand(0,2)],
                    'response_time' => rand(50, 2000),
                    'status_code' => [200, 500, null][rand(0,2)],
                    'checked_at' => Carbon::now()->subMinutes(rand(1, 500)),
                    'error_message' => null
                ]);
            }
        }
    }
}
