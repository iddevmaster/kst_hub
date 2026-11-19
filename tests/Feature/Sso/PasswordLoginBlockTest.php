<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordLoginBlockTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_sso_account_cannot_use_password_login(): void
    {
        $this->makeUser(['username' => '1000000000001', 'password' => 'known-pass', 'sso_source' => 'subscription']);
        $this->post('/login', ['username' => '1000000000001', 'password' => 'known-pass'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);
        $this->assertGuest();
    }

    public function test_public_login_route_is_gone(): void
    {
        $this->get('/public-login?username=a&password=b')->assertNotFound();
    }

    public function test_rotate_command_replaces_password_equal_to_username(): void
    {
        $user = $this->makeUser(['username' => '1000000000001', 'id_number' => '1000000000001', 'password' => '1000000000001']);
        $this->artisan('sso:rotate-legacy-passwords')->assertSuccessful();
        $this->assertFalse(Hash::check('1000000000001', $user->fresh()->password));
        $this->assertSame('idrives', $user->fresh()->sso_source);
    }

    public function test_rotate_dry_run_changes_nothing(): void
    {
        $user = $this->makeUser(['username' => '1000000000002', 'id_number' => '1000000000002', 'password' => '1000000000002']);
        $this->artisan('sso:rotate-legacy-passwords --dry-run')->expectsOutput('Would rotate 1 users.')->assertSuccessful();
        $this->assertTrue(Hash::check('1000000000002', $user->fresh()->password));
        $this->assertNull($user->fresh()->sso_source);
    }
}
