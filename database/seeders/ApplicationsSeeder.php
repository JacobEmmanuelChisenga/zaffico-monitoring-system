<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Applications;
use App\Models\User;
use App\Models\Tags;

class ApplicationsSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->first();

        $apps = [
            ['name' => 'DMS', 'ip_address' => '192.168.1.10', 'protocol' => 'http', 'endpoint' => '/health', 'created_by' => $admin->id],
            ['name' => 'HRS', 'ip_address' => '192.168.1.20', 'protocol' => 'https', 'endpoint' => '/status', 'created_by' => $admin->id],
            ['name' => 'Sage', 'ip_address' => '192.168.1.30', 'protocol' => 'ping', 'created_by' => $admin->id],
        ];

        foreach ($apps as $app) {
            $application = Applications::create($app);

            // Assign random tags
            $tags = Tags::inRandomOrder()->take(2)->pluck('id');
            $application->tags()->attach($tags);
        }
    }
}
