<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnFromTsmcTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    private function query(string $username): string
    {
        return '/learn-from-tsmc/1?' . http_build_query(['name' => 'X', 'username' => $username, 'org' => 'Org', 'is_tsm' => '1']);
    }

    public function test_sso_account_is_refused_and_not_logged_in(): void
    {
        $this->makeUser(['username' => '1000000000001', 'id_number' => '1000000000001', 'sso_source' => 'subscription']);
        $this->get($this->query('1000000000001'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_admin_account_is_refused_and_not_logged_in(): void
    {
        $this->makeUser(['username' => 'someadmin', 'role' => 'admin']);
        $this->get($this->query('someadmin'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_existing_tsmc_student_is_logged_in(): void
    {
        $u = $this->makeUser(['username' => 'tsmcperson', 'role' => 'TSMCStudent']);
        $this->get($this->query('tsmcperson'))->assertRedirect();
        $this->assertAuthenticatedAs($u);
    }
}
