<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\Applications;
use App\Models\DowntimeIncident;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $applications = Applications::all();

        foreach ($applications as $app) {
            $incident = DowntimeIncident::where('application_id', $app->id)->first();

            if ($incident) {
                Notification::create([
                    'application_id' => $app->id,
                    'incident_id' => $incident->id,
                    'type' => 'email',
                    'recipient' => 'admin@zaffico.com',
                    'subject' => 'Downtime Alert',
                    'message' => 'Your application experienced downtime.',
                    'status' => 'sent',
                    'sent_at' => Carbon::now()
                ]);
            }
        }
    }
}
