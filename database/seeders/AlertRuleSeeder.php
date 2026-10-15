<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AlertRule;
use App\Models\Applications;

class AlertRuleSeeder extends Seeder
{
    public function run()
    {
        $applications = Applications::all();

        foreach ($applications as $app) {
            AlertRule::create([
                'application_id' => $app->id,
                'trigger_type' => 'downtime',
                'consecutive_failures' => 3,
                'notification_channels' => json_encode(['email','sms']),
                'recipients' => json_encode(['admin@zaffico.com']),
                'is_active' => true
            ]);
        }
    }
}
