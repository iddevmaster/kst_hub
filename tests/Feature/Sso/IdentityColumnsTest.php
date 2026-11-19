<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentityColumnsTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_users_and_branches_have_sso_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['id_number', 'sso_source']));
        $this->assertTrue(Schema::hasColumn('branches', 'source'));
    }

    public function test_branch_source_is_fillable(): void
    {
        $agency = $this->makeAgency('IDD0002');
        $branch = $this->makeBranch($agency, 'uuid-1', 'โรงเรียน A', 'subscription');
        $this->assertSame('subscription', $branch->fresh()->source);
    }
}
