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

/**
 * The legacy SSO and the subscription SSO share the same SmartHub course groups. The sync only
 * lists codes; it must never create or alter groups, and login reuses the existing group by code.
 */
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

    private function existingGroup(string $code, string $name = 'กลุ่มเดิม', string $by = 'api:sso'): course_group
    {
        return course_group::create(['name' => $name, 'agn' => (string) $this->agency->id, 'by' => $by, 'code' => $code]);
    }

    private function groupIdsOf($user): array
    {
        return user_has_group::where('user_id', $user->id)->pluck('group_id')->map(fn ($v) => (int) $v)->all();
    }

    public function test_sync_lists_codes_and_never_touches_course_groups(): void
    {
        $existing = $this->existingGroup('20240001', 'รถยนต์');
        $this->fakeSso([
            ['code' => '20240001', 'name' => 'รถยนต์ (ชื่อใหม่ใน SSO)', 'is_active' => true],
            ['code' => '01', 'name' => 'รถยนต์ DTMS', 'is_active' => true],
            ['code' => '09', 'name' => 'เลิกใช้', 'is_active' => false],
        ]);

        $this->artisan('sso:sync-vehicle-types')
            ->expectsOutputToContain('2 added, 1 match an existing group by code, 0 mapped by admin, 1 without group, 1 skipped')
            ->expectsOutputToContain('No course group yet for code: 01')
            ->assertSuccessful();

        $this->assertSame(1, course_group::count());                       // nothing created
        $this->assertSame('รถยนต์', $existing->fresh()->name);              // nothing renamed
        $this->assertSame('api:sso', $existing->fresh()->by);              // not taken over
        $this->assertSame(['01', '20240001'], SsoCourseMap::where('source', 'subscription')->orderBy('code')->pluck('code')->all());

        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('0 added')->assertSuccessful();
        $this->assertSame(2, SsoCourseMap::count());
    }

    public function test_admin_mapped_codes_are_counted_and_not_overwritten(): void
    {
        $g = $this->existingGroup('20240001');
        SsoCourseMap::create(['source' => 'subscription', 'code' => '01', 'name' => 'x', 'course_group_id' => $g->id]);
        $this->fakeSso([['code' => '01', 'name' => 'รถยนต์ DTMS', 'is_active' => true]]);

        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('0 added, 0 match an existing group by code, 1 mapped by admin')->assertSuccessful();
        $this->assertSame($g->id, SsoCourseMap::where('code', '01')->value('course_group_id'));
        $this->assertSame('รถยนต์ DTMS', SsoCourseMap::where('code', '01')->value('name'));
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
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('1 added, 0 match an existing group by code, 0 mapped by admin, 1 without group, 4 skipped')->assertSuccessful();
    }

    public function test_command_fails_cleanly_when_sso_errors_or_lacks_the_endpoint(): void
    {
        foreach ([500, 404] as $status) {
            Http::swap(new Factory());
            Http::fake([
                'sso.test/oauth/token' => Http::response(['access_token' => 'cc', 'expires_in' => 3600], 200),
                'sso.test/api/vehicle-types' => Http::response('x', $status),
            ]);
            Cache::flush();
            $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain("HTTP $status")->assertFailed();
        }
        $this->assertSame(0, SsoCourseMap::count());
        $this->assertSame(0, course_group::count());
    }

    public function test_revoked_cached_token_is_retried_once_with_a_fresh_token(): void
    {
        Cache::put('sso_sub_roster_token', 'stale', 600);
        Http::swap(new Factory());
        Http::fake([
            'sso.test/oauth/token' => Http::response(['access_token' => 'fresh', 'expires_in' => 3600], 200),
            'sso.test/api/vehicle-types' => Http::sequence()
                ->push(['message' => 'Unauthenticated.'], 401)
                ->push(['data' => [['code' => '01', 'name' => 'รถยนต์', 'is_active' => true]]], 200),
        ]);
        $this->artisan('sso:sync-vehicle-types')->expectsOutputToContain('1 added')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains($r->url(), 'vehicle-types') && $r->hasHeader('Authorization', 'Bearer fresh'));
    }

    public function test_login_reuses_the_existing_group_with_the_same_code(): void
    {
        $existing = $this->existingGroup('20240001', 'รถยนต์');
        // same code in another agency (a different school group) must not be picked
        course_group::create(['name' => 'Other agency', 'agn' => '999', 'by' => 'api:sso', 'code' => '20240001']);

        $p = $this->subscriptionProfile(['username' => '1000000000001', 'vehicle_type' => '20240001']);
        $user = app(SsoUserProvisioner::class)->provisionSubscription($p);
        $this->assertSame([$existing->id], $this->groupIdsOf($user));
        $this->assertSame(2, course_group::count());   // nothing created by logging in
    }

    public function test_admin_mapping_wins_over_a_code_match(): void
    {
        $byCode = $this->existingGroup('01', 'same code');
        $other = $this->existingGroup('20240001', 'รถยนต์');
        SsoCourseMap::create(['source' => 'subscription', 'code' => '01', 'name' => 'x', 'course_group_id' => $other->id]);

        $user = app(SsoUserProvisioner::class)->provisionSubscription($this->subscriptionProfile(['username' => '1000000000001', 'vehicle_type' => '01']));
        $this->assertSame([$other->id], $this->groupIdsOf($user));
        $this->assertNotSame($byCode->id, $other->id);
    }

    public function test_code_without_a_group_still_logs_in_without_group(): void
    {
        $p = $this->subscriptionProfile(['username' => '1000000000002', 'vehicle_type' => 'NEW']);
        $user = app(SsoUserProvisioner::class)->provisionSubscription($p);
        $this->assertSame([], $this->groupIdsOf($user));
        $this->assertSame(1, SsoCourseMap::where('code', 'NEW')->count());
    }

    public function test_admin_sync_button_page_and_permissions(): void
    {
        $this->withoutVite();
        $this->existingGroup('20240001', 'รถยนต์เดิม');
        $this->fakeSso([['code' => '20240001', 'name' => 'รถยนต์', 'is_active' => true]]);
        Role::create(['name' => 'superAdmin']);
        $customer = $this->makeUser(['agency' => (string) $this->agency->id]);
        $this->actingAs($customer)->post(route('sso.course-maps.sync'))->assertForbidden();
        $this->assertSame(0, SsoCourseMap::count());

        $admin = $this->makeUser(['agency' => (string) $this->agency->id]);
        $admin->assignRole('superAdmin');
        $this->actingAs($admin)->post(route('sso.course-maps.sync'))->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, SsoCourseMap::where('code', '20240001')->count());
        $this->assertSame(1, course_group::count());
        $this->actingAs($admin)->get(route('sso.course-maps'))->assertOk()->assertSee('รถยนต์เดิม');

        Http::swap(new Factory());
        Http::fake(['sso.test/*' => Http::response('x', 503)]);
        Cache::flush();
        $this->actingAs($admin)->post(route('sso.course-maps.sync'))->assertRedirect()->assertSessionHas('sync_error');
    }
}
