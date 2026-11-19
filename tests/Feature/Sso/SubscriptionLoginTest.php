<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionLoginTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002',
            'services.sso_subscription.client_id' => 'cid', 'services.sso_subscription.client_secret' => 'sec',
            'services.sso_subscription.callback' => 'https://hub.test/auth/callback/subscription']);
        $this->makeAgency('IDD0002');
    }

    public function test_login_redirects_to_authorize_with_state(): void
    {
        $res = $this->get('/sso/subscription/login');
        $res->assertRedirect();
        $this->assertStringStartsWith('https://sso.test/oauth/authorize?', $res->headers->get('Location'));
        $this->assertNotEmpty(session('sso.state.subscription'));
    }

    public function test_bad_state_is_rejected(): void
    {
        $this->withSession(['sso.state.subscription' => 'good'])
            ->get('/auth/callback/subscription?code=c&state=bad')
            ->assertRedirect(route('loginpage'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_access_denied_is_handled(): void
    {
        $this->withSession(['sso.state.subscription' => 'good'])
            ->get('/auth/callback/subscription?error=access_denied&state=good')
            ->assertRedirect(route('loginpage'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_success_logs_in_and_stores_tokens(): void
    {
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'acc', 'refresh_token' => 'ref'], 200),
            'sso.test/api/user' => Http::response($this->subscriptionProfile(), 200),
        ]);
        $this->withSession(['sso.state.subscription' => 'good'])
            ->get('/auth/callback/subscription?code=c&state=good')
            ->assertRedirect(route('main'));
        $this->assertAuthenticated();
        $this->assertSame('subscription', session('sso.source'));
        $this->assertSame('acc', session('sso.access_token'));
    }

    public function test_403_shows_sso_message(): void
    {
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'acc'], 200),
            'sso.test/api/user' => Http::response(['error' => 'access_denied', 'reason' => 'no_entitlement', 'message' => 'โรงเรียนของคุณยังไม่ได้เปิดใช้บริการนี้'], 403),
        ]);
        $this->withSession(['sso.state.subscription' => 'good'])
            ->get('/auth/callback/subscription?code=c&state=good')
            ->assertRedirect(route('loginpage'))->assertSessionHas('error', 'โรงเรียนของคุณยังไม่ได้เปิดใช้บริการนี้');
        $this->assertGuest();
    }
}
