<?php

namespace Tests\Feature\Sso;

use App\Services\Sso\SubscriptionSsoClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionSsoClientTest extends TestCase
{
    use SsoTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.client_id' => 'cid',
            'services.sso_subscription.client_secret' => 'sec', 'services.sso_subscription.callback' => 'https://hub.test/cb']);
    }

    public function test_fetch_user_ok(): void
    {
        Http::fake(['sso.test/api/user' => Http::response($this->subscriptionProfile(), 200)]);
        $res = app(SubscriptionSsoClient::class)->fetchUser('tok');
        $this->assertSame('ok', $res->status);
        $this->assertSame('1000000000001', $res->profile['username']);
    }

    public function test_fetch_user_denied_carries_message(): void
    {
        Http::fake(['sso.test/api/user' => Http::response(['error' => 'access_denied', 'reason' => 'enrollment_ended', 'message' => 'สิทธิ์การเรียนของคุณสิ้นสุดแล้ว'], 403)]);
        $res = app(SubscriptionSsoClient::class)->fetchUser('tok');
        $this->assertSame('denied', $res->status);
        $this->assertSame('สิทธิ์การเรียนของคุณสิ้นสุดแล้ว', $res->message);
    }

    public function test_fetch_user_401_and_5xx(): void
    {
        Http::fakeSequence('sso.test/api/user')->push(['message' => 'Unauthenticated.'], 401)->push('down', 503);
        $client = app(SubscriptionSsoClient::class);
        $this->assertSame('unauthorized', $client->fetchUser('tok')->status);
        $this->assertSame('error', $client->fetchUser('tok')->status);
    }

    public function test_exchange_code_requires_access_token(): void
    {
        Http::fakeSequence('sso.test/oauth/token')
            ->push(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 86400], 200)
            ->push(['error' => 'invalid_grant'], 400);
        $client = app(SubscriptionSsoClient::class);
        $this->assertSame('a', $client->exchangeCode('c')['access_token']);
        $this->assertNull($client->exchangeCode('c'));
    }

    public function test_authorize_url(): void
    {
        $url = app(SubscriptionSsoClient::class)->authorizeUrl('st');
        $this->assertStringStartsWith('https://sso.test/oauth/authorize?', $url);
        $this->assertStringContainsString('state=st', $url);
        $this->assertStringContainsString('scope=view-user', $url);
    }
}
