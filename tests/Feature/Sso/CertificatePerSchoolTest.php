<?php

namespace Tests\Feature\Sso;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\course;
use App\Models\Test as TestRecord;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CertificatePerSchoolTest extends TestCase
{
    use RefreshDatabase, SsoTestHelpers;

    public function test_passing_same_course_in_two_schools_gives_two_certificates(): void
    {
        $agency = $this->makeAgency('IDD0002');
        $a = $this->makeBranch($agency, 'uuid-a', 'โรงเรียน A', 'subscription');
        $b = $this->makeBranch($agency, 'uuid-b', 'โรงเรียน B', 'subscription');
        $user = $this->makeUser(['agency' => (string) $agency->id, 'brn' => (string) $a->id]);

        $course = new course(); $course->id = 10; $course->title = 'รถยนต์'; $course->agn = (string) $agency->id;
        $tpl = new CertificateTemplate(); $tpl->id = 5;

        $svc = Mockery::mock(CertificateService::class)->makePartial();
        $svc->shouldReceive('courseForTest')->andReturn($course);
        $svc->shouldReceive('findTemplate')->andReturn($tpl);

        $first = $svc->issueForTest($this->passedTest($user));
        $user->forceFill(['brn' => (string) $b->id])->save();
        $second = $svc->issueForTest($this->passedTest($user));

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('โรงเรียน A', $first->snapshot['school']);
        $this->assertSame('โรงเรียน B', $second->snapshot['school']);
        $this->assertSame(2, Certificate::where('user_id', $user->id)->count());
    }

    public function test_backfill_issues_one_certificate_per_school_and_is_idempotent(): void
    {
        $agency = $this->makeAgency('IDD0003');
        $a = $this->makeBranch($agency, 'uuid-a', 'โรงเรียน A', 'subscription');
        $b = $this->makeBranch($agency, 'uuid-b', 'โรงเรียน B', 'subscription');
        $user = $this->makeUser(['agency' => (string) $agency->id, 'brn' => (string) $a->id]);
        $now = now();

        $courseId = DB::table('courses')->insertGetId([
            'title' => 'รถยนต์', 'permission' => '1', 'teacher' => '1', 'dpm' => '', 'code' => 'C1',
            'agn' => (string) $agency->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $quizId = DB::table('quizzes')->insertGetId([
            'title' => 'Quiz', 'pass_score' => 50, 'create_by' => '1',
            'agn' => (string) $agency->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('course_has_quizzes')->insert(['course_id' => $courseId, 'quiz_id' => $quizId]);
        DB::table('certificate_templates')->insert([
            'name' => 'T', 'agn' => (string) $agency->id, 'course_id' => null, 'bg_image' => 'x.png',
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ([$a, $b] as $branch) {
            DB::table('tests')->insert([
                'quiz' => (string) $quizId, 'tester' => (string) $user->id, 'answers' => '[]',
                'score' => 10, 'totalScore' => 10, 'agn' => (string) $agency->id, 'brn' => (string) $branch->id,
                'course_id' => $courseId, 'end' => $now->format('Y-m-d H:i:s'),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $svc = new CertificateService();
        $this->assertSame(2, $svc->backfill((string) $agency->id)['issued']);

        $certs = Certificate::where('user_id', $user->id)->orderBy('brn')->get();
        $this->assertCount(2, $certs);
        $byBrn = $certs->keyBy('brn');
        $this->assertSame('โรงเรียน A', $byBrn[(string) $a->id]->snapshot['school']);
        $this->assertSame('โรงเรียน B', $byBrn[(string) $b->id]->snapshot['school']);

        $this->assertSame(0, $svc->backfill((string) $agency->id)['issued']);
        $this->assertSame(2, Certificate::where('user_id', $user->id)->count());
    }

    private function passedTest($user): TestRecord
    {
        $test = Mockery::mock(TestRecord::class)->makePartial();
        $test->forceFill(['id' => random_int(1, 99999), 'tester' => (string) $user->id, 'score' => 10, 'totalScore' => 10, 'end' => now()->format('Y-m-d H:i:s')]);
        $test->shouldReceive('isPassed')->andReturn(true);
        $test->shouldReceive('getAttribute')->with('getQuiz')->andReturn((object) ['id' => 1, 'title' => 'Quiz']);
        return $test;
    }
}
