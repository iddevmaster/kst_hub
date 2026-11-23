<?php

namespace App\Console\Commands;

use App\Services\Sso\VehicleTypeSync;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SyncSsoVehicleTypes extends Command
{
    protected $signature = 'sso:sync-vehicle-types';
    protected $description = 'Import the Subscription SSO vehicle-type codes into the mapping page (never creates course groups)';

    public function handle(VehicleTypeSync $sync): int
    {
        try {
            $r = $sync->run();
        } catch (RequestException $e) {
            $this->error('SSO returned HTTP ' . $e->response?->status());
            return self::FAILURE;
        } catch (ConnectionException) {
            $this->error('Could not reach the SSO.');
            return self::FAILURE;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info("Vehicle types: {$r['added']} added, {$r['matched']} match an existing group by code, {$r['mapped']} mapped by admin, {$r['unmapped']} without group, {$r['skipped']} skipped.");
        if ($r['unmapped_codes']) {
            $this->warn('No course group yet for code: ' . implode(', ', $r['unmapped_codes']));
        }
        return self::SUCCESS;
    }
}
