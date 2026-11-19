<?php

namespace Tests\Feature\Sso;

use App\Models\agency;
use App\Models\course_group;
use App\Models\SsoCourseMap;
use App\Models\user_has_group;
use App\Services\Sso\SsoLoginRejected;
use App\Services\Sso\SsoUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoUserProvisionerTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    private function prov(): SsoUserProvisioner { return app(SsoUserProvisioner::class); }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.agency' => 'IDD0002']);
    }

    public function test_same_id_two_schools_same_agency_is_one_user_and_switches_branch(): void
    {
        $agency = $this->makeAgency('IDD0002');
        $u1 = $this->prov()->provisionSubscription($this->subscriptionProfile());
        $u2 = $this->prov()->provisionSubscription($this->subscriptionProfile([
            'sub' => '99999999-1111-4222-8333-444455556666',
            'school' => ['id' => 'school-b-uuid', 'name' => 'โรงเรียน B'],
        ]));

        $this->assertSame($u1->id, $u2->id);
        $this->assertSame((string) $agency->id, $u2->agency);
        $this->assertSame('โรงเรียน B', $u2->fresh()->getBrn->name);
        $this->assertSame('subscription', $u2->getBrn->source);
    }

    public function test_same_id_in_other_agency_gets_separate_user_with_suffixed_username(): void
    {
        $this->makeAgency('IDD0002');
        $tz = $this->makeAgency('TZ0003');
        $legacy = $this->makeUser(['username' => '1000000000001', 'id_number' => '1000000000001', 'agency' => (string) $tz->id]);

        $user = $this->prov()->provisionSubscription($this->subscriptionProfile());

        $this->assertNotSame($legacy->id, $user->id);
        $this->assertSame('1000000000001-IDD0002', $user->username);
        $this->assertSame('1000000000001', $user->id_number);
    }

    public function test_legacy_row_without_id_number_is_adopted(): void
    {
        $agency = $this->makeAgency('IDD0002');
        $legacy = $this->makeUser(['username' => '1000000000001', 'agency' => (string) $agency->id]);

        $user = $this->prov()->provisionSubscription($this->subscriptionProfile());

        $this->assertSame($legacy->id, $user->id);
        $this->assertSame('1000000000001', $user->fresh()->id_number);
        $this->assertSame('1000000000001', $user->username);
    }

    public function test_password_is_random_not_id_number(): void
    {
        $this->makeAgency('IDD0002');
        $user = $this->prov()->provisionSubscription($this->subscriptionProfile());
        $this->assertFalse(\Hash::check('1000000000001', $user->fresh()->password));
    }

    public function test_unmapped_code_is_recorded_and_user_has_no_group(): void
    {
        $this->makeAgency('IDD0002');
        $user = $this->prov()->provisionSubscription($this->subscriptionProfile());
        $this->assertTrue(SsoCourseMap::where('source', 'subscription')->where('code', 'CAR')->whereNull('course_group_id')->exists());
        $this->assertSame(0, user_has_group::where('user_id', $user->id)->count());
    }

    public function test_mapped_group_replaces_old_groups(): void
    {
        $agency = $this->makeAgency('IDD0002');
        $car = course_group::create(['name' => 'car', 'agn' => (string) $agency->id, 'by' => 'admin', 'code' => '1']);
        $moto = course_group::create(['name' => 'moto', 'agn' => (string) $agency->id, 'by' => 'admin', 'code' => '2']);
        SsoCourseMap::create(['source' => 'subscription', 'code' => 'CAR', 'course_group_id' => $car->id]);
        SsoCourseMap::create(['source' => 'subscription', 'code' => 'MOTO', 'course_group_id' => $moto->id]);

        $user = $this->prov()->provisionSubscription($this->subscriptionProfile(['vehicle_type' => 'MOTO', 'courses' => [['course_type' => 'MOTO', 'name' => 'จักรยานยนต์']]]));
        $user = $this->prov()->provisionSubscription($this->subscriptionProfile(['school' => ['id' => 'school-b-uuid', 'name' => 'โรงเรียน B']]));

        $this->assertSame([$car->id], user_has_group::where('user_id', $user->id)->pluck('group_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_missing_school_is_rejected(): void
    {
        $this->makeAgency('IDD0002');
        $this->expectException(SsoLoginRejected::class);
        $profile = $this->subscriptionProfile();
        $profile['school'] = null;
        $this->prov()->provisionSubscription($profile);
    }

    private function idrivesProfile(array $overrides = []): array
    {
        return array_replace_recursive([
            'username' => 'u-1',
            'name' => 'N',
            'role' => 'customer',
            'agency' => ['agn_id' => 'TZ0003', 'name' => 'TZ'],
            'branch' => ['brn_id' => 'b-1', 'name' => 'Branch 1'],
            'courses' => [['course_type' => 'C1', 'name' => 'Course 1']],
        ], $overrides);
    }

    public function test_idrives_happy_path_creates_user_agency_and_group(): void
    {
        $user = $this->prov()->provisionIdrives($this->idrivesProfile());

        $agency = agency::where('agn_id', 'TZ0003')->firstOrFail();
        $this->assertSame((string) $agency->id, $user->agency);
        $this->assertSame('idrives', $user->sso_source);
        $group = course_group::where('code', 'C1')->where('by', 'api:sso')->firstOrFail();
        $this->assertSame([$group->id], user_has_group::where('user_id', $user->id)->pluck('group_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_idrives_agency_name_fallback_only_adopts_null_agn_id(): void
    {
        $other = agency::create(['name' => 'TZ', 'agn_id' => 'OTHER1']);

        $user = $this->prov()->provisionIdrives($this->idrivesProfile());

        $this->assertSame('OTHER1', $other->fresh()->agn_id);
        $this->assertNotSame((string) $other->id, $user->agency);
        $this->assertSame(2, agency::count());
    }

    public function test_idrives_empty_courses_is_rejected(): void
    {
        $this->expectException(SsoLoginRejected::class);
        $profile = $this->idrivesProfile();
        $profile['courses'] = [];
        $this->prov()->provisionIdrives($profile);
    }
}
