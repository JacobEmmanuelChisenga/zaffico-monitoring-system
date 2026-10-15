<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            UserSeeder::class,
            TagsSeeder::class,
            ApplicationsSeeder::class,
            MonitoringCheckSeeder::class,
            DowntimeIncidentSeeder::class,
            AlertRuleSeeder::class,
            MaintenanceWindowSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
