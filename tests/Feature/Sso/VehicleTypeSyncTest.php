<?php

namespace Tests\Feature\Sso;

use App\Models\course_group;
use App\Models\SsoCourseMap;
use App\Models\user_has_group;
use App\Services\Sso\SsoUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleTypeSyncTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    private $agency;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.host' => 'https://sso.test', 'services.sso_subscription.agency' => 'IDD0002',
            'services.sso_subscription.client_id' => 'cid', 'services.sso_subscription.client_secret' => 'sec']);
        Cache::flush();
        $this->agency = $this->makeAgency('IDD0002');
        Role::create(['name' => 'customer']);
    }

    private function fakeSso(array $types): void
    {
        Http::swap(new Factory());   // Http::fake() only adds stubs; start clean so the newest wins
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'cc', 'expires_in' => 3600], 200),
            'sso.test/api/vehicle-types' => Http::response(['data' => $types], 200),
        ]);
    }

    private function groupIdsOf($user): array
    {
        return user_has_group::where('user_id', $user->id)->pluck('group_id')->map(fn ($v) => (int) $v)->all();
    }

    public function test_command_creates_groups_keeps_leading_zero_and_is_idempotent(): void
    {
        $this->fakeSso([
            ['code' => '01', 'name' => 'รถยนต์', 'is_active' => true],
            ['code' => '02', 'name' => 'รถจักรยานยนต์', 'is_active' => true],
            ['code' => '09', 'name' => 'เลิกใช้', 'is_active' => false],
        ]);

        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('2 created, 0 updated, 0 unchanged, 1 skipped')->assertSuccessful();

        $g = course_group::where('code', '01')->first();
        $this->assertSame('รถยนต์', $g->name);
        $this->assertSame('api:sso-sub', $g->by);
        $this->assertSame((string) $this->agency->id, $g->agn);
        $this->assertNull(course_group::where('code', '09')->first());
        $this->assertSame(['01', '02'], SsoCourseMap::where('source', 'subscription')->orderBy('code')->pluck('code')->all());

        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('0 created, 0 updated, 2 unchanged')->assertSuccessful();
        $this->assertSame(2, course_group::count());
    }

    public function test_rename_updates_the_group_and_legacy_group_with_same_code_is_left_alone(): void
    {
        $legacy = course_group::create(['name' => 'Legacy', 'agn' => '99', 'by' => 'api:sso', 'code' => '01']);
        $this->fakeSso([['code' => '01', 'name' => 'รถยนต์', 'is_active' => true]]);
        $this->artisan('sso:sync-vehicle-types')->assertSuccessful();

        $this->assertSame('Legacy', $legacy->fresh()->name);
        $this->assertSame(2, course_group::where('code', '01')->count());

        $this->fakeSso([['code' => '01', 'name' => 'รถยนต์ (ใหม่)', 'is_active' => true]]);
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('1 updated')->assertSuccessful();
        $this->assertSame('รถยนต์ (ใหม่)', course_group::where('code', '01')->where('by', 'api:sso-sub')->value('name'));
        $this->assertSame('Legacy', $legacy->fresh()->name);
    }

    public function test_malformed_rows_are_skipped(): void
    {
        $this->fakeSso([
            ['code' => 1, 'name' => 'numeric code'],
            ['code' => '', 'name' => 'empty'],
            ['code' => 'X', 'name' => ''],
            ['name' => 'no code'],
            ['code' => 'OK', 'name' => 'ok'],
        ]);
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('1 created, 0 updated, 0 unchanged, 4 skipped')->assertSuccessful();
    }

    public function test_command_fails_cleanly_when_sso_errors(): void
    {
        Http::swap(new Factory());
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'cc', 'expires_in' => 3600], 200),
            'sso.test/api/vehicle-types' => Http::response('boom', 500),
        ]);
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('HTTP 500')->assertFailed();
        $this->assertSame(0, course_group::count());
    }

    public function test_revoked_cached_token_is_retried_once_with_a_fresh_token(): void
    {
        Cache::put('sso_sub_roster_token', 'stale', 600);
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'fresh', 'expires_in' => 3600], 200),
            'sso.test/api/vehicle-types' => Http::sequence()
                ->push(['message' => 'Unauthenticated.'], 401)
                ->push(['data' => [['code' => '01', 'name' => 'รถยนต์', 'is_active' => true]]], 200),
        ]);
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('1 created')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains($r->url(), 'vehicle-types') && $r->hasHeader('Authorization', 'Bearer fresh'));
    }

    public function test_login_uses_the_synced_group_by_code_and_admin_mapping_overrides_it(): void
    {
        $this->fakeSso([['code' => '01', 'name' => 'รถยนต์', 'is_active' => true]]);
        $this->artisan('sso:sync-vehicle-types')->assertSuccessful();
        $auto = course_group::where('code', '01')->where('by', 'api:sso-sub')->first();
        course_group::create(['name' => 'Legacy', 'agn' => '99', 'by' => 'api:sso', 'code' => '01']);

        $p = $this->subscriptionProfile(['username' => '1000000000001', 'vehicle_type' => '01']);
        $user = app(SsoUserProvisioner::class)->provisionSubscription($p);
        $this->assertSame([$auto->id], $this->groupIdsOf($user));

        // an admin picks another group for the code: the explicit mapping wins
        $other = course_group::create(['name' => 'Other', 'agn' => (string) $this->agency->id, 'by' => 'admin', 'code' => 'o']);
        SsoCourseMap::where('code', '01')->update(['course_group_id' => $other->id]);
        $user = app(SsoUserProvisioner::class)->provisionSubscription($p);
        $this->assertSame([$other->id], $this->groupIdsOf($user));
    }

    public function test_code_without_a_synced_group_still_logs_in_without_group(): void
    {
        $p = $this->subscriptionProfile(['username' => '1000000000002', 'vehicle_type' => 'NEW']);
        $user = app(SsoUserProvisioner::class)->provisionSubscription($p);
        $this->assertSame([], $this->groupIdsOf($user));
        $this->assertSame(1, SsoCourseMap::where('code', 'NEW')->count());
    }

    public function test_admin_sync_button_and_permissions(): void
    {
        $this->withoutVite();
        $this->fakeSso([['code' => '01', 'name' => 'รถยนต์', 'is_active' => true]]);
        Role::create(['name' => 'superAdmin']);
        $customer = $this->makeUser(['agency' => (string) $this->agency->id]);
        $this->actingAs($customer)->post(route('sso.course-maps.sync'))->assertForbidden();
        $this->assertSame(0, course_group::count());

        $admin = $this->makeUser(['agency' => (string) $this->agency->id]);
        $admin->assignRole('superAdmin');
        $this->actingAs($admin)->post(route('sso.course-maps.sync'))->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, course_group::where('code', '01')->count());
        $this->actingAs($admin)->get(route('sso.course-maps'))->assertOk()->assertSee('รถยนต์');

        Http::swap(new Factory());
        Http::fake(['sso.test/*' => Http::response('x', 503)]);
        Cache::flush();
        $this->actingAs($admin)->post(route('sso.course-maps.sync'))->assertRedirect()->assertSessionHas('sync_error');
    }
}
