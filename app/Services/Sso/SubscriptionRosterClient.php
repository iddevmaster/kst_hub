<?php

namespace App\Services\Sso;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SubscriptionRosterClient
{
    private function url(string $path): string
    {
        return rtrim((string) config('services.sso_subscription.host'), '/') . $path;
    }

    private function token(): string
    {
        $cached = Cache::get('sso_sub_roster_token');
        if (is_string($cached)) {
            return $cached;
        }
        $res = Http::asForm()->acceptJson()->timeout(10)->post($this->url('/oauth/token'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.sso_subscription.client_id'),
            'client_secret' => config('services.sso_subscription.client_secret'),
            'scope' => 'view-roster',
        ])->throw();
        $token = (string) $res->json('access_token');
        $ttl = max(60, (int) $res->json('expires_in', 3600) - 60);
        Cache::put('sso_sub_roster_token', $token, $ttl);
        return $token;
    }

    /**
     * Vehicle types (course types) known to the SSO. One retry with a fresh token when the cached
     * one was revoked or expired early.
     *
     * @return array<int, array{code:string,name:string,is_active:bool}>
     */
    public function vehicleTypes(): array
    {
        $call = fn () => Http::acceptJson()->withToken($this->token())->timeout(10)->get($this->url('/api/vehicle-types'));
        $res = $call();
        if ($res->status() === 401) {
            Cache::forget('sso_sub_roster_token');
            $res = $call();
        }
        return $res->throw()->json('data', []);
    }

    /** @return array<int, array{id:string,code:string,name:string,is_active:bool}> */
    public function branches(string $schoolId): array
    {
        return Http::acceptJson()->withToken($this->token())->timeout(10)
            ->get($this->url('/api/schools/' . rawurlencode($schoolId) . '/branches'))
            ->throw()->json('data', []);
    }

    /** @return array<string, bool> id_number => active, every page followed */
    public function roster(string $schoolId, string $branchId): array
    {
        $out = [];
        $cursor = null;
        do {
            $query = array_filter(['limit' => 5000, 'cursor' => $cursor]);
            $res = Http::acceptJson()->withToken($this->token())->timeout(20)
                ->get($this->url('/api/schools/' . rawurlencode($schoolId) . '/branches/' . rawurlencode($branchId) . '/roster'), $query)
                ->throw();
            foreach ($res->json('data', []) as $row) {
                $out[(string) $row['id_number']] = (bool) $row['active'];
            }
            $cursor = $res->json('next_cursor');
        } while (is_string($cursor) && $cursor !== '');
        return $out;
    }
}
