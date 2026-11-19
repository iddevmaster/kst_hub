<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolRosterTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_roster_filters_users_of_selected_school_branch_across_pages(): void
    {
        $this->withoutVite();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002',
            'services.sso_subscription.client_id' => 'cid', 'services.sso_subscription.client_secret' => 'sec']);
        $agency = $this->makeAgency('IDD0002');
        $school = $this->makeBranch($agency, 'school-uuid', 'โรงเรียน A', 'subscription');
        $this->makeUser(['name' => 'Alice', 'id_number' => '1000000000001', 'agency' => (string) $agency->id, 'brn' => (string) $school->id]);
        $this->makeUser(['name' => 'Bob', 'id_number' => '1000000000002', 'agency' => (string) $agency->id, 'brn' => (string) $school->id]);
        $this->makeUser(['name' => 'Carol', 'id_number' => '1000000000003', 'agency' => (string) $agency->id, 'brn' => (string) $school->id]);

        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'cc', 'expires_in' => 3600], 200),
            'sso.test/api/schools/school-uuid/branches' => Http::response(['data' => [['id' => 'sb-1', 'code' => 'HQ', 'name' => 'สำนักงานใหญ่', 'is_active' => true]]], 200),
            'sso.test/api/schools/school-uuid/branches/sb-1/roster*' => Http::sequence()
                ->push(['data' => [['id_number' => '1000000000001', 'active' => true]], 'next_cursor' => '11111111-1111-4222-8333-444455556666'], 200)
                ->push(['data' => [['id_number' => '1000000000002', 'active' => false]], 'next_cursor' => null], 200),
        ]);

        Role::create(['name' => 'superAdmin']);
        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');

        $this->actingAs($admin)->get(route('sso.roster', ['school' => $school->id, 'branch' => 'sb-1']))
            ->assertOk()->assertSee('Alice')->assertSee('Bob')->assertDontSee('Carol')
            ->assertDontSee('1000000000001');
    }

    public function test_non_admin_is_forbidden_and_foreign_school_is_ignored(): void
    {
        $this->withoutVite();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002']);
        $agency = $this->makeAgency('IDD0002');
        $other = $this->makeAgency('OTHER');
        $foreign = $this->makeBranch($other, 'foreign-uuid', 'Foreign', 'subscription');
        Http::fake();

        $user = $this->makeUser(['agency' => (string) $agency->id]);
        $this->actingAs($user)->get(route('sso.roster'))->assertForbidden();

        Role::create(['name' => 'superAdmin']);
        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');
        $this->actingAs($admin)->get(route('sso.roster', ['school' => $foreign->id, 'branch' => 'x']))->assertOk();
        Http::assertNothingSent();
    }

    public function test_sso_outage_shows_friendly_message(): void
    {
        $this->withoutVite();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002']);
        $agency = $this->makeAgency('IDD0002');
        $school = $this->makeBranch($agency, 'school-uuid', 'A', 'subscription');
        Http::fake(['sso.test/*' => Http::response([], 500)]);
        Role::create(['name' => 'superAdmin']);
        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');

        $this->actingAs($admin)->get(route('sso.roster', ['school' => $school->id]))
            ->assertOk()->assertSee(__('messages.sso_roster_error'));
    }
}
