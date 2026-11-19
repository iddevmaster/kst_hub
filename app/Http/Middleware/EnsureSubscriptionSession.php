<?php

namespace App\Http\Middleware;

use App\Services\Sso\SubscriptionSsoClient;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subscription SSO users: re-check GET /api/user at most every `recheck_seconds`.
 * 401 → refresh once; 403 → logout with the SSO message; outage → allow for
 * `outage_grace_seconds` after the last successful check, then logout.
 */
class EnsureSubscriptionSession
{
    public function __construct(private SubscriptionSsoClient $client)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $sso = $request->session()->get('sso', []);
        if (Auth::check() && isset($sso['user_id']) && (string) $sso['user_id'] !== (string) Auth::id()) {
            // SSO data left over from another user of this browser: never attach it to this account.
            $request->session()->forget('sso');
            return $next($request);
        }
        if (!Auth::check() || ($sso['source'] ?? null) !== 'subscription') {
            return $next($request);
        }

        $now = time();
        if ($now - (int) ($sso['checked_at'] ?? 0) < (int) config('services.sso_subscription.recheck_seconds', 60)) {
            return $next($request);
        }

        $res = $this->client->fetchUser((string) ($sso['access_token'] ?? ''));
        if ($res->status === 'unauthorized' && !empty($sso['refresh_token'])) {
            $tokens = $this->client->refresh((string) $sso['refresh_token']);
            if ($tokens) {
                $sso['access_token'] = $tokens['access_token'];
                $sso['refresh_token'] = $tokens['refresh_token'] ?? $sso['refresh_token'];
                $res = $this->client->fetchUser($sso['access_token']);
            }
        }

        switch ($res->status) {
            case 'ok':
                $sso['checked_at'] = $sso['last_ok_at'] = $now;
                $request->session()->put('sso', $sso);
                return $next($request);
            case 'denied':
                return $this->logout($request, $res->message ?? SubscriptionSsoClient::GENERIC_DENIED);
            case 'unauthorized':
                return $this->logout($request, 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่จาก portal');
            default: // error / outage
                if ($now - (int) ($sso['last_ok_at'] ?? 0) <= (int) config('services.sso_subscription.outage_grace_seconds', 1800)) {
                    $sso['checked_at'] = $now;
                    $request->session()->put('sso', $sso);
                    return $next($request);
                }
                return $this->logout($request, 'ระบบยืนยันตัวตนขัดข้อง กรุณาลองใหม่ภายหลัง');
        }
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Livewire's persistent-middleware runner ignores a returned response and a 302 is
        // misleading for XHR, so signal "unauthenticated" with an exception instead.
        if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
            $request->session()->flash('error', $message);
            throw new AuthenticationException($message);
        }

        return redirect()->route('loginpage')->with('error', $message);
    }
}
