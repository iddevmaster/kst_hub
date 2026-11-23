<?php

namespace Tests\Feature\Sso;

use App\Livewire\OwnGroupCourse;
use App\Models\course_group;
use App\Models\SsoCourseMap;
use App\Models\user_has_group;
use App\Services\Sso\SsoUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admins can give a group the code that SSO students are matched on. */
class GroupCodeTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    private $agency;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso_subscription.agency' => 'IDD0002']);
        $this->agency = $this->makeAgency('IDD0002');
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
        $this->admin = $this->makeUser(['agency' => (string) $this->agency->id]);
        $this->admin->assignRole('admin');
    }

    private function add(array $data)
    {
        return $this->actingAs($this->admin)->post(route('courses.add.group'), $data + ['gname' => 'กลุ่มทดสอบ', 'selected_course' => []]);
    }

    public function test_admin_creates_a_group_with_a_code(): void
    {
        $this->add(['gcode' => '20240001'])->assertSessionHasNoErrors();
        $g = course_group::where('name', 'กลุ่มทดสอบ')->first();
        $this->assertSame('20240001', $g->code);
        $this->assertSame((string) $this->agency->id, $g->agn);
    }

    public function test_code_is_optional_and_blank_is_stored_as_null(): void
    {
        $this->add(['gcode' => ''])->assertSessionHasNoErrors();
        $this->add(['gcode' => '   ', 'gname' => 'อีกกลุ่ม'])->assertSessionHasNoErrors();
        $this->assertSame(2, course_group::whereNull('code')->count());
    }

    public function test_duplicate_code_is_rejected_even_across_agencies(): void
    {
        course_group::create(['name' => 'เดิม', 'by' => 'api:sso', 'agn' => '99', 'code' => '20240001']);
        $this->add(['gcode' => '20240001'])->assertSessionHasErrors('gcode');
        $this->assertSame(1, course_group::count());
    }

    public function test_bad_characters_and_length_are_rejected(): void
    {
        $this->add(['gcode' => 'มี ช่องว่าง'])->assertSessionHasErrors('gcode');
        $this->add(['gcode' => 'a b'])->assertSessionHasErrors('gcode');
        $this->add(['gcode' => str_repeat('1', 51)])->assertSessionHasErrors('gcode');
        $this->assertSame(0, course_group::count());
    }

    public function test_edit_sets_changes_and_clears_the_code(): void
    {
        $g = course_group::create(['name' => 'ของฉัน', 'by' => (string) $this->admin->id, 'agn' => (string) $this->agency->id]);
        $this->actingAs($this->admin);

        Livewire::test(OwnGroupCourse::class, ['gid' => $g->id])->set('gCode', 'ABC-1')->call('updateGroup')->assertHasNoErrors();
        $this->assertSame('ABC-1', $g->fresh()->code);

        Livewire::test(OwnGroupCourse::class, ['gid' => $g->id])->set('gCode', '')->call('updateGroup')->assertHasNoErrors();
        $this->assertNull($g->fresh()->code);
    }

    public function test_edit_rejects_a_code_used_by_another_group_but_keeps_its_own(): void
    {
        course_group::create(['name' => 'อื่น', 'by' => 'x', 'agn' => '1', 'code' => 'TAKEN']);
        $g = course_group::create(['name' => 'ของฉัน', 'by' => (string) $this->admin->id, 'agn' => (string) $this->agency->id, 'code' => 'MINE']);
        $this->actingAs($this->admin);

        Livewire::test(OwnGroupCourse::class, ['gid' => $g->id])->set('gCode', 'TAKEN')->call('updateGroup')->assertHasErrors('gCode');
        $this->assertSame('MINE', $g->fresh()->code);

        // saving again with its own unchanged code is fine
        Livewire::test(OwnGroupCourse::class, ['gid' => $g->id])->call('updateGroup')->assertHasNoErrors();
        $this->assertSame('MINE', $g->fresh()->code);
    }

    public function test_group_synced_from_the_legacy_sso_keeps_its_code(): void
    {
        $g = course_group::create(['name' => 'จาก SSO', 'by' => 'api:sso', 'agn' => (string) $this->agency->id, 'code' => '20240001']);
        $this->actingAs($this->admin);

        Livewire::test(OwnGroupCourse::class, ['gid' => $g->id])->set('gCode', 'HACK')->set('gName', 'ชื่อใหม่')->call('updateGroup')->assertHasNoErrors();
        $this->assertSame('20240001', $g->fresh()->code);
        $this->assertSame('ชื่อใหม่', $g->fresh()->name);
    }

    public function test_a_group_the_admin_coded_receives_sso_students(): void
    {
        $this->add(['gcode' => '20240009'])->assertSessionHasNoErrors();
        $g = course_group::where('code', '20240009')->first();

        $user = app(SsoUserProvisioner::class)->provisionSubscription(
            $this->subscriptionProfile(['username' => '1000000000001', 'vehicle_type' => '20240009']));
        $this->assertSame([$g->id], user_has_group::where('user_id', $user->id)->pluck('group_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(0, SsoCourseMap::whereNotNull('course_group_id')->count());
    }
}
