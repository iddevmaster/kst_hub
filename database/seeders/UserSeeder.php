<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the user with the specified username already exists
        $existingUser = User::where('username', 'superadmin')->first();

        if (!$existingUser) {
            // User doesn't exist, so create it
            $adminUser = User::create([
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => bcrypt('iddrivesadmin'), // Use bcrypt() to hash the password
                'agency' => '0',
                'brn' => '0',
                'dpm' => '0',
                'role' => 'superAdmin',
                'icon' => '',
                'courses' => [],
                // ... any other user fields
            ]);

            if (!($adminUser->hasRole('superAdmin'))) {
                $adminUser->assignRole('superAdmin');
            }

            $this->command->info("User superAdmin created successfully.");
        } else {
            // User already exists, display a message
            $this->command->info("User superAdmin already exists.");
        }
    }
}
