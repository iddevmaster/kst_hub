<?php

namespace App\Console\Commands;

use App\Services\Sso\VehicleTypeSync;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SyncSsoVehicleTypes extends Command
{
    protected $signature = 'sso:sync-vehicle-types';
    protected $description = 'Create/update course groups from the Subscription SSO vehicle types';

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
        $this->info("Vehicle types: {$r['created']} created, {$r['updated']} updated, {$r['unchanged']} unchanged, {$r['skipped']} skipped.");
        return self::SUCCESS;
    }
}
