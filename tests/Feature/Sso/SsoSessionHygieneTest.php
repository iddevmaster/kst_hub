<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SsoSessionHygieneTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    private function stale(?int $userId = null): array
    {
        return ['sso' => ['source' => 'subscription', 'access_token' => 'old', 'refresh_token' => 'oldr',
            'checked_at' => time(), 'last_ok_at' => time(), 'user_id' => $userId],
            'access_token' => 'legacy', 'refresh_token' => 'legacyr'];
    }

    public function test_login_page_forgets_stale_sso_data(): void
    {
        $this->withoutVite();
        $this->withSession($this->stale(1))->get('/')->assertOk();
        $this->assertNull(session('sso'));
        $this->assertNull(session('access_token'));
        $this->assertNull(session('refresh_token'));
    }

    public function test_password_login_does_not_inherit_stale_sso_data(): void
    {
        $this->makeUser(['username' => 'staffer', 'password' => 'secret-password']);
        $this->withSession($this->stale(999))->post('/login', ['username' => 'staffer', 'password' => 'secret-password']);
        $this->assertAuthenticated();
        $this->assertNull(session('sso'));
        $this->assertNull(session('access_token'));
    }

    public function test_callback_stores_user_id_in_sso_session(): void
    {
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002',
            'services.sso_subscription.client_id' => 'cid', 'services.sso_subscription.client_secret' => 'sec',
            'services.sso_subscription.callback' => 'https://hub.test/auth/callback/subscription']);
        $this->makeAgency('IDD0002');
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'acc', 'refresh_token' => 'ref'], 200),
            'sso.test/api/user' => Http::response($this->subscriptionProfile(), 200),
        ]);
        $this->withSession(['sso.state.subscription' => 'good'])->get('/auth/callback/subscription?code=c&state=good');
        $this->assertSame(\Auth::id(), session('sso.user_id'));
        $this->assertNotNull(session('sso.user_id'));
    }

    public function test_middleware_ignores_sso_data_of_another_user(): void
    {
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.recheck_seconds' => 60]);
        Route::middleware(['web', 'auth', 'sso.subscription'])->get('/_test/ping2', fn () => 'ok');
        Http::fake();
        $user = $this->makeUser();
        $session = $this->stale($user->id + 1000);
        $session['sso']['checked_at'] = time() - 600; // would trigger a check / denial if honoured
        $this->actingAs($user)->withSession($session)->get('/_test/ping2')->assertOk();
        Http::assertNothingSent();
        $this->assertNull(session('sso'));
    }
}
