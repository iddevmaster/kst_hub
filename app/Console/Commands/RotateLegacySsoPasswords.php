<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RotateLegacySsoPasswords extends Command
{
    protected $signature = 'sso:rotate-legacy-passwords {--dry-run : Only count, change nothing}';
    protected $description = 'Give SSO-created users whose password equals their username a random password and tag them as SSO users';

    public function handle(): int
    {
        $found = 0;
        User::whereNotNull('id_number')->whereNull('sso_source')->orderBy('id')
            ->chunkById(500, function ($users) use (&$found) {
                foreach ($users as $user) {
                    if (!Hash::check((string) $user->username, (string) $user->password)) {
                        continue;
                    }
                    $found++;
                    if (!$this->option('dry-run')) {
                        $user->forceFill(['password' => Str::random(64), 'sso_source' => 'idrives'])->save();
                    }
                }
            });
        $this->info(($this->option('dry-run') ? 'Would rotate ' : 'Rotated ') . $found . ' users.');
        return self::SUCCESS;
    }
}
