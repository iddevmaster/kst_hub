<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureSubscriptionSessionTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.recheck_seconds' => 60,
            'services.sso_subscription.outage_grace_seconds' => 1800]);
        Route::middleware(['web', 'auth', 'sso.subscription'])->get('/_test/ping', fn () => 'ok');
    }

    private function ssoSession(int $checkedAgo, int $okAgo): array
    {
        return ['sso' => ['source' => 'subscription', 'access_token' => 'acc', 'refresh_token' => 'ref',
            'checked_at' => time() - $checkedAgo, 'last_ok_at' => time() - $okAgo]];
    }

    public function test_recent_check_skips_sso_call(): void
    {
        Http::fake();
        $this->actingAs($this->makeUser())->withSession($this->ssoSession(10, 10))->get('/_test/ping')->assertOk();
        Http::assertNothingSent();
    }

    public function test_denied_logs_out_with_message(): void
    {
        Http::fake(['sso.test/api/user' => Http::response(['error' => 'access_denied', 'message' => 'บัญชีของคุณถูกระงับ กรุณาติดต่อโรงเรียน'], 403)]);
        $this->actingAs($this->makeUser())->withSession($this->ssoSession(120, 120))->get('/_test/ping')
            ->assertRedirect(route('loginpage'))->assertSessionHas('error', 'บัญชีของคุณถูกระงับ กรุณาติดต่อโรงเรียน');
        $this->assertGuest();
    }

    public function test_401_refreshes_once_then_passes(): void
    {
        Http::fake([
            'sso.test/api/user' => Http::sequence()->push([], 401)->push($this->subscriptionProfile(), 200),
            'sso.test/oauth/token' => Http::response(['access_token' => 'new', 'refresh_token' => 'ref2'], 200),
        ]);
        $this->actingAs($this->makeUser())->withSession($this->ssoSession(120, 120))->get('/_test/ping')->assertOk();
        $this->assertSame('new', session('sso.access_token'));
    }

    public function test_outage_within_grace_passes(): void
    {
        Http::fake(['sso.test/api/user' => Http::response('down', 503)]);
        $this->actingAs($this->makeUser())->withSession($this->ssoSession(120, 600))->get('/_test/ping')->assertOk();
    }

    public function test_outage_beyond_grace_logs_out(): void
    {
        Http::fake(['sso.test/api/user' => Http::response('down', 503)]);
        $this->actingAs($this->makeUser())->withSession($this->ssoSession(120, 4000))->get('/_test/ping')
            ->assertRedirect(route('loginpage'));
        $this->assertGuest();
    }

    public function test_other_sources_are_untouched(): void
    {
        Http::fake();
        $this->actingAs($this->makeUser())->withSession(['sso' => ['source' => 'idrives']])->get('/_test/ping')->assertOk();
        Http::assertNothingSent();
    }
}
