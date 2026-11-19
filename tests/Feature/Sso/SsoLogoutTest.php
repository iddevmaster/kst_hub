<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoLogoutTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_subscription_user_logout_revokes_on_new_sso_only(): void
    {
        config(['services.sso_subscription.host' => 'https://sso.test', 'auth.sso_host' => 'https://old.test']);
        Http::fake();
        $this->actingAs($this->makeUser())
            ->withSession(['sso' => ['source' => 'subscription', 'access_token' => 'acc', 'checked_at' => time(), 'last_ok_at' => time()]])
            ->post(route('logout'))->assertRedirect('/');

        Http::assertSent(fn ($r) => $r->url() === 'https://sso.test/api/logout' && $r->hasHeader('Authorization', 'Bearer acc'));
        Http::assertNotSent(fn ($r) => str_starts_with($r->url(), 'https://old.test'));
        $this->assertGuest();
    }
}
