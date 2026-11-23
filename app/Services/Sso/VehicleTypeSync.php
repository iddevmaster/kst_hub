<?php

namespace App\Services\Sso;

use App\Models\agency;
use App\Models\course_group;
use App\Models\SsoCourseMap;

/**
 * Mirrors the SSO's vehicle types into course groups (same idea as the legacy
 * /api/courseType sync): one group per code, in the subscription agency, tagged so it never
 * collides with a legacy group that happens to use the same code.
 */
class VehicleTypeSync
{
    /** course_groups.by value of groups created here. */
    public const SOURCE_TAG = 'api:sso-sub';

    public function __construct(private SubscriptionRosterClient $client)
    {
    }

    /** @return array{created:int,updated:int,unchanged:int,skipped:int} */
    public function run(): array
    {
        $agency = agency::where('agn_id', (string) config('services.sso_subscription.agency'))->first();
        if (!$agency) {
            throw new \RuntimeException('Subscription agency is not configured.');
        }

        $out = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        foreach ($this->client->vehicleTypes() as $item) {
            $code = $item['code'] ?? null;
            $name = $item['name'] ?? null;
            // Codes are strings ("01" must stay "01"); anything else is a malformed row.
            if (!is_string($code) || $code === '' || strlen($code) > 50 || !is_string($name) || $name === ''
                || ($item['is_active'] ?? true) === false) {
                $out['skipped']++;
                continue;
            }

            // Lists every code on the mapping page before the first student logs in.
            SsoCourseMap::firstOrCreate(['source' => 'subscription', 'code' => $code], ['name' => $name]);

            $group = course_group::where('code', $code)->where('by', self::SOURCE_TAG)->where('agn', (string) $agency->id)->first();
            if (!$group) {
                course_group::create(['name' => $name, 'agn' => (string) $agency->id, 'by' => self::SOURCE_TAG, 'code' => $code]);
                $out['created']++;
            } elseif ($group->name !== $name) {
                $group->update(['name' => $name]);
                $out['updated']++;
            } else {
                $out['unchanged']++;
            }
        }
        return $out;
    }

    /** The group created by the sync for this code, if any. */
    public static function groupIdFor(string $code, agency $agency): ?int
    {
        $id = course_group::where('code', $code)->where('by', self::SOURCE_TAG)->where('agn', (string) $agency->id)->value('id');
        return $id === null ? null : (int) $id;
    }
}
