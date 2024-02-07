<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createRoleIfNotExists('superAdmin');
        $this->createRoleIfNotExists('admin');
        $this->createRoleIfNotExists('staff');
        $this->createRoleIfNotExists('teacher');
        $this->createRoleIfNotExists('employee');
        $this->createRoleIfNotExists('new');
    }

    private function createRoleIfNotExists(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            Role::create(['name' => $roleName]);
            $this->command->info("Role '{$roleName}' created successfully.");
        } else {
            $this->command->info("Role '{$roleName}' already exists.");
        }
    }

}
