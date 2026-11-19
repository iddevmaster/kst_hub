<?php

namespace App\Services\Sso;

use Illuminate\Support\Facades\Http;

class SubscriptionSsoClient
{
    public const GENERIC_DENIED = 'ไม่สามารถเข้าใช้งานได้';

    private function cfg(string $key): mixed
    {
        return config("services.sso_subscription.{$key}");
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->cfg('host'), '/') . $path;
    }

    public function authorizeUrl(string $state): string
    {
        return $this->url('/oauth/authorize?' . http_build_query([
            'client_id' => $this->cfg('client_id'),
            'redirect_uri' => $this->cfg('callback'),
            'response_type' => 'code',
            'scope' => $this->cfg('scopes'),
            'state' => $state,
        ]));
    }

    /** @return array{access_token:string,refresh_token:?string}|null */
    public function exchangeCode(string $code): ?array
    {
        return $this->token([
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->cfg('callback'),
            'code' => $code,
        ]);
    }

    /** @return array{access_token:string,refresh_token:?string}|null */
    public function refresh(string $refreshToken): ?array
    {
        return $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    private function token(array $form): ?array
    {
        try {
            $res = Http::asForm()->acceptJson()->timeout(10)->post($this->url('/oauth/token'), $form + [
                'client_id' => $this->cfg('client_id'),
                'client_secret' => $this->cfg('client_secret'),
            ]);
        } catch (\Throwable) {
            return null;
        }
        $access = $res->json('access_token');
        if (!$res->successful() || !is_string($access) || $access === '') {
            return null;
        }
        return ['access_token' => $access, 'refresh_token' => $res->json('refresh_token')];
    }

    public function fetchUser(string $accessToken): SsoUserResponse
    {
        try {
            $res = Http::acceptJson()->withToken($accessToken)->timeout(5)->get($this->url('/api/user'));
        } catch (\Throwable) {
            return new SsoUserResponse('error');
        }

        if ($res->status() === 200 && is_string($res->json('sub')) && is_string($res->json('username'))) {
            return new SsoUserResponse('ok', $res->json());
        }
        if ($res->status() === 401) {
            return new SsoUserResponse('unauthorized');
        }
        if ($res->status() === 403) {
            $message = $res->json('error') === 'access_denied' ? $res->json('message') : null;
            return new SsoUserResponse('denied', [], is_string($message) && $message !== '' ? $message : self::GENERIC_DENIED);
        }
        return new SsoUserResponse('error');
    }

    public function logout(string $accessToken): void
    {
        try {
            Http::acceptJson()->withToken($accessToken)->timeout(5)->post($this->url('/api/logout'));
        } catch (\Throwable) {
            // Silent: the local session is cleared regardless.
        }
    }
}
