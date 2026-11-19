<?php

namespace Tests\Feature\Sso;

use App\Models\course_group;
use App\Models\SsoCourseMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SsoCourseMapTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_admin_maps_code_to_group_of_subscription_agency(): void
    {
        config(['services.sso_subscription.agency' => 'IDD0002']);
        $agency = $this->makeAgency('IDD0002');
        $group = course_group::create(['name' => 'รถยนต์', 'agn' => (string) $agency->id, 'by' => 'admin', 'code' => '20240001']);
        $map = SsoCourseMap::create(['source' => 'subscription', 'code' => 'CAR', 'name' => 'รถยนต์']);

        Role::create(['name' => 'superAdmin']);
        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');

        $this->actingAs($admin)->post(route('sso.course-maps.update', $map->id), ['course_group_id' => $group->id])
            ->assertRedirect();
        $this->assertSame($group->id, $map->fresh()->course_group_id);
    }

    public function test_rejects_group_from_other_agency(): void
    {
        config(['services.sso_subscription.agency' => 'IDD0002']);
        $agency = $this->makeAgency('IDD0002');
        $other = $this->makeAgency('TZ0003');
        $group = course_group::create(['name' => 'x', 'agn' => (string) $other->id, 'by' => 'admin', 'code' => 'x']);
        $map = SsoCourseMap::create(['source' => 'subscription', 'code' => 'CAR']);

        Role::create(['name' => 'superAdmin']);
        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');

        $this->actingAs($admin)->post(route('sso.course-maps.update', $map->id), ['course_group_id' => $group->id])
            ->assertSessionHasErrors('course_group_id');
    }

    public function test_customer_forbidden_and_admin_sees_code(): void
    {
        $this->withoutVite();
        config(['services.sso_subscription.agency' => 'IDD0002']);
        $agency = $this->makeAgency('IDD0002');
        $map = SsoCourseMap::create(['source' => 'subscription', 'code' => 'CAR', 'name' => 'รถยนต์']);

        Role::create(['name' => 'superAdmin']);
        $customer = $this->makeUser(['agency' => (string) $agency->id]);
        $this->actingAs($customer)->get(route('sso.course-maps'))->assertForbidden();
        $this->actingAs($customer)->post(route('sso.course-maps.update', $map->id), ['course_group_id' => null])->assertForbidden();

        $admin = $this->makeUser(['agency' => (string) $agency->id]);
        $admin->assignRole('superAdmin');
        $this->actingAs($admin)->get(route('sso.course-maps'))->assertOk()->assertSee('CAR');
    }

    public function test_admin_of_other_agency_is_forbidden_and_sees_no_nav_link(): void
    {
        $this->withoutVite();
        config(['services.sso_subscription.agency' => 'IDD0002']);
        $sub = $this->makeAgency('IDD0002');
        $other = $this->makeAgency('TZ0003');
        $map = SsoCourseMap::create(['source' => 'subscription', 'code' => 'CAR']);

        Role::create(['name' => 'admin']);
        $foreign = $this->makeUser(['agency' => (string) $other->id]);
        $foreign->assignRole('admin');
        $this->actingAs($foreign)->get(route('sso.course-maps'))->assertForbidden();
        $this->actingAs($foreign)->get(route('sso.roster'))->assertForbidden();
        $this->actingAs($foreign)->post(route('sso.course-maps.update', $map->id), ['course_group_id' => null])->assertForbidden();

        $own = $this->makeUser(['agency' => (string) $sub->id]);
        $own->assignRole('admin');
        $this->actingAs($own)->get(route('sso.course-maps'))->assertOk();
        $this->actingAs($own)->get(route('sso.course-maps'))->assertSee(route('sso.roster'), false);
        $this->actingAs($foreign)->get(route('main'))->assertDontSee(route('sso.roster'), false);
    }
}
