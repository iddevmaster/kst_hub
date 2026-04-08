<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\user_has_course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    protected $signature = 'app:generate-users {prefix} {count} {org_id} {brn_id} {dpm_id} {role?} {gen?} {course_id?}';

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
        $course_id = $this->argument('course_id');

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
            $username = "{$prefix}0{$i}2569";
            $name = "นักเรียนรุ่นที่{$gen} หมายเลข{$i}";
            try {
                DB::beginTransaction();
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

                if (!user_has_course::where('user_id', $user->id)->where('course_id', $course_id)->exists()) {
                    user_has_course::create([
                        'user_id' => $user->id,
                        'course_id' => $course_id
                    ]);
                }

                DB::commit();

                $this->info(
                    "Generated user: {$username}" .
                    " | Name: นักเรียนรุ่นที่{$gen} หมายเลข{$i}"
                );
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to create user {$username}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("---------- Successfully generated ----------");
    }
}
