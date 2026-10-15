<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@zaffico.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'receive_alerts' => true,
            'phone_number' => '+260764000001'
        ]);

        User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@zaffico.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'receive_alerts' => true,
            'phone_number' => '+260764000002'
        ]);

        User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@zaffico.com',
            'password' => Hash::make('password123'),
            'role' => 'viewer',
            'receive_alerts' => false
        ]);
    }
}
