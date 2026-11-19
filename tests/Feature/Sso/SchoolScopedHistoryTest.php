<?php

namespace Tests\Feature\Sso;

use App\Models\user_has_course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolScopedHistoryTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_enrolled_page_hides_previous_school_courses(): void
    {
        $user = $this->makeUser(['brn' => '1', 'sso_source' => 'subscription']);
        $a = $this->course('Course of A');
        $b = $this->course('Course of B');
        user_has_course::create(['user_id' => $user->id, 'course_id' => $a]);
        $user->forceFill(['brn' => '2'])->save();
        user_has_course::create(['user_id' => $user->id, 'course_id' => $b]);

        $this->withoutVite();
        $this->actingAs($user)->get(route('courses-enrolled'))
            ->assertOk()->assertSee('Course of B')->assertDontSee('Course of A');
    }

    private function course(string $title): int
    {
        $cols = collect(Schema::getColumnListing('courses'))->reject(fn ($c) => in_array($c, ['id', 'created_at', 'updated_at', 'deleted_at']));
        $row = $cols->mapWithKeys(fn ($c) => [$c => $c === 'permission' ? '{}' : 'x'])->all();
        $row['title'] = $title;
        return DB::table('courses')->insertGetId($row);
    }
}
