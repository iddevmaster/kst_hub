<?php

namespace App\Services\Sso;

use App\Models\agency;
use App\Models\course_group;
use App\Models\SsoCourseMap;

/**
 * Imports the SSO's list of vehicle types (course types) into the code list of the mapping page,
 * so an admin can see every code before the first student logs in.
 *
 * It never creates or changes course groups: the legacy SSO and the subscription SSO share the
 * same SmartHub course groups (and the courses attached to them). A student gets the group whose
 * `code` equals the code the SSO sends, or the group an admin mapped to that code.
 */
class VehicleTypeSync
{
    public function __construct(private SubscriptionRosterClient $client)
    {
    }

    /** @return array{added:int,matched:int,mapped:int,unmapped:int,skipped:int,unmapped_codes:array<int,string>} */
    public function run(): array
    {
        $agency = agency::where('agn_id', (string) config('services.sso_subscription.agency'))->first();
        if (!$agency) {
            throw new \RuntimeException('Subscription agency is not configured.');
        }

        $out = ['added' => 0, 'matched' => 0, 'mapped' => 0, 'unmapped' => 0, 'skipped' => 0, 'unmapped_codes' => []];
        foreach ($this->client->vehicleTypes() as $item) {
            $code = $item['code'] ?? null;
            $name = $item['name'] ?? null;
            // Codes are strings ("01" must stay "01"); anything else is a malformed row.
            if (!is_string($code) || $code === '' || strlen($code) > 50 || !is_string($name) || $name === ''
                || ($item['is_active'] ?? true) === false) {
                $out['skipped']++;
                continue;
            }

            $map = SsoCourseMap::firstOrCreate(['source' => 'subscription', 'code' => $code], ['name' => $name]);
            if ($map->wasRecentlyCreated) {
                $out['added']++;
            } elseif ($map->name !== $name) {
                $map->update(['name' => $name]);
            }

            if ($map->course_group_id) {
                $out['mapped']++;
            } elseif (self::groupIdForCode($code, $agency) !== null) {
                $out['matched']++;
            } else {
                $out['unmapped']++;
                $out['unmapped_codes'][] = $code;
            }
        }
        return $out;
    }

    /** Existing group (any origin) of the subscription agency whose code equals the SSO's code. */
    public static function groupIdForCode(string $code, agency $agency): ?int
    {
        $id = course_group::where('code', $code)->where('agn', (string) $agency->id)->orderBy('id')->value('id');
        return $id === null ? null : (int) $id;
    }
}
