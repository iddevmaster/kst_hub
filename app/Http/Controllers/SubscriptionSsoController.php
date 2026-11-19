<?php

namespace App\Http\Controllers;

use App\Services\Sso\SsoLoginRejected;
use App\Services\Sso\SsoUserProvisioner;
use App\Services\Sso\SubscriptionSsoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionSsoController extends Controller
{
    private const GENERIC = 'ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง';

    public function __construct(private SubscriptionSsoClient $client, private SsoUserProvisioner $provisioner)
    {
    }

    public function login(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('sso.state.subscription', $state);
        return redirect()->away($this->client->authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $expected = (string) $request->session()->pull('sso.state.subscription', '');
        if ($expected === '' || !hash_equals($expected, (string) $request->query('state', ''))) {
            return $this->fail(self::GENERIC);
        }
        if ($request->query('error') === 'access_denied') {
            return $this->fail('คุณไม่ได้อนุญาตให้ SmartHub เข้าถึงข้อมูล');
        }
        $code = $request->query('code');
        if (!is_string($code) || $code === '') {
            return $this->fail(self::GENERIC);
        }

        $tokens = $this->client->exchangeCode($code);
        if (!$tokens) {
            return $this->fail(self::GENERIC);
        }

        $res = $this->client->fetchUser($tokens['access_token']);
        if ($res->status === 'denied') {
            return $this->fail($res->message ?? SubscriptionSsoClient::GENERIC_DENIED);
        }
        if ($res->status !== 'ok') {
            return $this->fail(self::GENERIC);
        }

        try {
            $user = $this->provisioner->provisionSubscription($res->profile);
        } catch (SsoLoginRejected $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            // Never log the exception message: SQL bindings would include the ID number.
            Log::error('Subscription SSO provisioning failed', ['exception' => get_class($e), 'at' => basename($e->getFile()) . ':' . $e->getLine(), 'sub' => $res->profile['sub'] ?? null]);
            return $this->fail(self::GENERIC);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $now = time();
        $request->session()->put('sso', [
            'source' => 'subscription',
            'user_id' => $user->id,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'checked_at' => $now,
            'last_ok_at' => $now,
        ]);
        return redirect()->route('main');
    }

    private function fail(string $message)
    {
        return redirect()->route('loginpage')->with('error', $message);
    }
}
