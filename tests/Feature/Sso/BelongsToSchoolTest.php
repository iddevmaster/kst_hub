<?php

namespace Tests\Feature\Sso;

use App\Models\progress;
use App\Models\Test as TestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToSchoolTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_creating_stamps_users_current_branch(): void
    {
        $user = $this->makeUser(['brn' => '7']);
        $p = progress::create(['user_id' => $user->id, 'course_id' => '1', 'lesson_id' => '1', 'agn' => '1']);
        $this->assertSame('7', $p->fresh()->brn);
    }

    public function test_test_model_uses_tester_column(): void
    {
        $user = $this->makeUser(['brn' => '9']);
        $t = TestRecord::forceCreate(['quiz' => '1', 'tester' => (string) $user->id, 'answers' => '[]', 'agn' => '1']);
        $this->assertSame('9', $t->fresh()->brn);
    }

    public function test_scope_returns_only_current_school_for_sso_users(): void
    {
        $user = $this->makeUser(['brn' => '1', 'sso_source' => 'subscription']);
        progress::create(['user_id' => $user->id, 'course_id' => '1', 'lesson_id' => '1', 'agn' => '1']);
        $user->forceFill(['brn' => '2'])->save();
        progress::create(['user_id' => $user->id, 'course_id' => '1', 'lesson_id' => '2', 'agn' => '1']);

        $this->assertSame(['2'], progress::ofUserSchool($user)->pluck('lesson_id')->all());
    }

    public function test_scope_keeps_whole_history_for_non_sso_users(): void
    {
        $user = $this->makeUser(['brn' => '1', 'sso_source' => null]);
        progress::create(['user_id' => $user->id, 'course_id' => '1', 'lesson_id' => '1', 'agn' => '1']);
        $user->forceFill(['brn' => '2'])->save();
        progress::create(['user_id' => $user->id, 'course_id' => '1', 'lesson_id' => '2', 'agn' => '1']);

        $this->assertEqualsCanonicalizing(['1', '2'], progress::ofUserSchool($user)->pluck('lesson_id')->all());
    }
}
