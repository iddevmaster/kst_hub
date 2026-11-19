<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoryBrnColumnsTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_history_tables_have_brn(): void
    {
        foreach (['progress', 'tests', 'user_has_courses', 'certificates'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'brn'), "$table.brn missing");
        }
    }

    public function test_same_course_can_be_enrolled_in_two_schools(): void
    {
        $user = $this->makeUser();
        $courseId = DB::table('courses')->insertGetId($this->courseRow());

        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '1']);
        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '2']);

        $this->assertSame(2, DB::table('user_has_courses')->where('user_id', $user->id)->count());
    }

    public function test_same_school_cannot_enroll_twice_in_same_course(): void
    {
        $user = $this->makeUser();
        $courseId = DB::table('courses')->insertGetId($this->courseRow());

        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '1']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '1']);
    }

    public function test_certificate_unique_key_includes_brn(): void
    {
        $user = $this->makeUser();
        $courseId = DB::table('courses')->insertGetId($this->courseRow());

        $row = fn (string $no, string $brn) => [
            'cert_no' => $no, 'user_id' => $user->id, 'course_id' => $courseId, 'agn' => '1', 'brn' => $brn,
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('certificates')->insert($row('C-1', '1'));
        DB::table('certificates')->insert($row('C-2', '2'));

        $this->assertSame(2, DB::table('certificates')->where('user_id', $user->id)->count());

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('certificates')->insert($row('C-3', '2'));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_20_000002_add_brn_to_history_tables.php');
    }

    public function test_migration_can_run_again_without_losing_data(): void
    {
        $user = $this->makeUser(['brn' => '5']);
        $courseId = DB::table('courses')->insertGetId($this->courseRow());
        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '5']);

        $this->migration()->up();

        $this->assertSame(1, DB::table('user_has_courses')->where('user_id', $user->id)->where('brn', '5')->count());
        $this->assertTrue(Schema::hasColumn('certificates', 'brn'));
        $this->assertFalse(Schema::hasTable('user_has_courses_new'));
    }

    public function test_migration_recovers_when_it_died_before_the_final_rename(): void
    {
        $user = $this->makeUser(['brn' => '5']);
        $courseId = DB::table('courses')->insertGetId($this->courseRow());
        DB::table('user_has_courses')->insert(['user_id' => $user->id, 'course_id' => $courseId, 'brn' => '5']);
        Schema::rename('user_has_courses', 'user_has_courses_new');

        $this->migration()->up();

        $this->assertTrue(Schema::hasTable('user_has_courses'));
        $this->assertFalse(Schema::hasTable('user_has_courses_new'));
        $this->assertSame(1, DB::table('user_has_courses')->count());
    }

    private function courseRow(): array
    {
        // Fill every NOT NULL column of `courses` without a default; adjust if the schema differs.
        $cols = collect(Schema::getColumnListing('courses'))->reject(fn ($c) => in_array($c, ['id', 'created_at', 'updated_at', 'deleted_at']));
        return $cols->mapWithKeys(fn ($c) => [$c => $c === 'studens' ? '[]' : ($c === 'permission' ? '{}' : 'x')])->all();
    }
}
