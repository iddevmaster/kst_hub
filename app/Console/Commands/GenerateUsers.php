<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * prefix - prefix for user names
     * gen - generation identifier
     * count - number of users to generate
     * org_id - organization ID (optional)
     * brn_id - branch ID (optional)
     * dpm_id - department ID (optional)
     */
    protected $signature = 'app:generate-users {prefix} {count} {org_id} {brn_id} {dpm_id} {role?} {gen?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate multiple user accounts with specified parameters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $prefix = $this->argument('prefix');
        $gen = $this->argument('gen');
        $count = (int) $this->argument('count');
        $orgId = $this->argument('org_id');
        $brnId = $this->argument('brn_id');
        $dpmId = $this->argument('dpm_id');
        $role = $this->argument('role');

        if ( User::where('username', 'LIKE', "{$prefix}%")->exists() ) {
             $this->error("Users with the prefix '{$prefix}' already exist. Aborting to prevent duplicates.");
             return;
        }

        $this->info(
            "---------- Start generat {$count} user role {$role}" .
                " | Org ID: {$orgId}" .
                " | Branch ID: {$brnId}" .
                " | Department ID: {$dpmId}". " ----------"
        );
        for ($i = 1; $i <= $count; $i++) {
            $username = "{$prefix}-0{$i}";
            $name = "นักเรียนรุ่นที่{$gen} หมายเลข{$i}";
            try {
                $user = User::create([
                    'name' => $name,
                    'username' => $username,
                    'password' => $username,
                    'agency' => $orgId,
                    'brn' => $brnId,
                    'dpm' => $dpmId ?? '',
                    'role' => $role ?? 'employee',
                ]);

                $user->assignRole($role ?? 'employee');

                $this->info(
                    "Generated user: {$username}" .
                    " | Name: นักเรียนรุ่นที่{$gen} หมายเลข{$i}"
                );
            } catch (\Exception $e) {
                $this->error("Failed to create user {$username}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("---------- Successfully generated ----------");
    }
}
