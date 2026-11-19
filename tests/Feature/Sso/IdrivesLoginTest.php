<?php

namespace Tests\Feature\Sso;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdrivesLoginTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.sso_host' => 'https://old.test', 'auth.client_id' => '1', 'auth.client_secret' => 's', 'auth.callback' => 'https://hub.test/auth/callback']);
    }

    private function oldProfile(array $o = []): array
    {
        return array_replace_recursive([
            'name' => 'นาย เดิม', 'username' => '2000000000002', 'role' => 'customer',
            'courses' => [['course_type' => '20240001', 'name' => 'รถยนต์']],
            'branch' => ['name' => 'โนนทัน', 'brn_id' => 'IDD0003'],
            'agency' => ['name' => 'โรงเรียนสอนขับรถ ไอดี ไดร์ฟเวอร์', 'agn_id' => 'IDD0002'],
        ], $o);
    }

    public function test_state_mismatch_is_rejected(): void
    {
        $this->withSession(['state' => 'good'])->get('/auth/callback?code=c&state=bad')
            ->assertRedirectContains('https://old.test');
        Http::assertNothingSent();
    }

    public function test_connect_provisions_and_logs_in(): void
    {
        Http::fake(['old.test/api/user' => Http::response($this->oldProfile(), 200)]);
        $this->withSession(['access_token' => 'acc'])->get('/sso/connect')->assertRedirect(route('main'));
        $user = User::where('id_number', '2000000000002')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('idrives', $user->sso_source);
    }

    public function test_exception_message_is_not_leaked(): void
    {
        Http::fake(['old.test/api/user' => Http::response($this->oldProfile(['agency' => ['agn_id' => '']]), 200)]);
        $res = $this->withSession(['access_token' => 'acc'])->get('/sso/connect');
        $this->assertStringContainsString(urlencode('Failed to get agency information! Try again.'), $res->headers->get('Location'));
    }
}
