<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\course;
use App\Models\course_has_quiz;
use App\Models\Test;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CertificateService
{
    /** A4 landscape in points (dompdf unit). */
    public const PAGE_W = 841.89;
    public const PAGE_H = 595.28;

    /**
     * Pick the active template for a course: course-specific first, then the agency default.
     */
    public function findTemplate(course $course, ?User $user = null): ?CertificateTemplate
    {
        $agns = array_values(array_unique(array_filter([$course->agn, $user?->agency])));

        $base = CertificateTemplate::where('active', true)->whereIn('agn', $agns);

        return (clone $base)->where('course_id', $course->id)->orderByRaw('agn = ? desc', [$course->agn])->first()
            ?? (clone $base)->whereNull('course_id')->orderByRaw('agn = ? desc', [$course->agn])->first();
    }

    /** Course a test belongs to; old rows have no course_id so fall back to the quiz's course. */
    public function courseForTest(Test $test): ?course
    {
        if ($test->course_id && ($c = course::find($test->course_id))) {
            return $c;
        }
        $cid = course_has_quiz::where('quiz_id', $test->quiz)->value('course_id');
        return $cid ? course::find($cid) : null;
    }

    /**
     * Issue (or refresh) the certificate for a passed test. Returns null when the test did not
     * pass, the quiz/course/user is gone, or the course has no active template.
     */
    public function issueForTest(Test $test): ?Certificate
    {
        $quiz = $test->getQuiz;
        if (!$quiz || !$test->isPassed()) {
            return null;
        }
        $user = User::find($test->tester);
        $course = $this->courseForTest($test);
        if (!$user || !$course) {
            return null;
        }
        $template = $this->findTemplate($course, $user);
        if (!$template) {
            return null;
        }

        $percent = $test->totalScore > 0 ? round($test->score / $test->totalScore * 100, 2) : 0;

        return DB::transaction(function () use ($test, $quiz, $user, $course, $template, $percent) {
            $cert = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->lockForUpdate()->first();

            if ($cert) {
                // Keep the existing number/date; only upgrade when the new attempt scored higher.
                if ($percent > (float) $cert->percent) {
                    $cert->update([
                        'quiz_id' => $quiz->id, 'test_id' => $test->id, 'template_id' => $template->id,
                        'score' => $test->score, 'total_score' => $test->totalScore, 'percent' => $percent,
                    ]);
                }
                return $cert;
            }

            $agn = $course->agn ?: $user->agency;
            return Certificate::create([
                'cert_no' => $this->nextNumber($agn),
                'user_id' => $user->id,
                'course_id' => $course->id,
                'quiz_id' => $quiz->id,
                'test_id' => $test->id,
                'template_id' => $template->id,
                'score' => $test->score,
                'total_score' => $test->totalScore,
                'percent' => $percent,
                'issued_at' => $test->end ? Carbon::parse($test->end) : now(),
                'snapshot' => [
                    'name' => $user->name,
                    'course' => $course->title,
                    'quiz' => $quiz->title,
                    'agency' => optional($user->agnName)->name,
                ],
                'agn' => $agn,
            ]);
        });
    }

    /** SH-{agn}-{YY}-{00001}; called inside a transaction, unique index is the backstop. */
    private function nextNumber(string $agn): string
    {
        $prefix = sprintf('SH-%s-%s-', $agn, now()->format('y'));
        $last = Certificate::where('cert_no', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('cert_no')->value('cert_no');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /** Issue certificates for every passing test of an agency that doesn't have one yet. */
    public function backfill(?string $agn): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        // Existing certificates: never touched by a backfill.
        $existing = [];
        foreach (DB::table('certificates')->select('user_id', 'course_id')->cursor() as $r) {
            $existing[$r->user_id . ':' . $r->course_id] = true;
        }

        $quizCourse = [];      // quiz id => first course id (for old tests without course_id)
        foreach (DB::table('course_has_quizzes')->orderBy('id')->get(['quiz_id', 'course_id']) as $r) {
            $quizCourse[$r->quiz_id] ??= $r->course_id;
        }
        $courses = [];         // course id => course|null
        $tplCache = [];        // "courseId:userAgency" => template|null
        $users = [];           // user id => User|null

        $skipped = 0;
        $pending = [];         // "user:course" => best attempt so far (+ date of the first pass)

        // Passing attempts only, decided in SQL; a removed quiz or test never qualifies.
        $q = DB::table('tests')
            ->join('quizzes', 'quizzes.id', '=', 'tests.quiz')
            ->whereNull('tests.deleted_at')->whereNull('quizzes.deleted_at')
            ->whereRaw('tests.score >= tests.totalScore * quizzes.pass_score / 100')
            ->select('tests.id', 'tests.quiz', 'tests.tester', 'tests.course_id', 'tests.score',
                'tests.totalScore', 'tests.end', 'quizzes.title as quiz_title');
        if ($agn) {
            $q->where('tests.agn', $agn);
        }

        $q->chunkById(1000, function ($rows) use (&$existing, &$pending, &$skipped, &$users, &$courses, &$tplCache, $quizCourse) {
            // One query for this chunk's users instead of one per row.
            $need = $rows->pluck('tester')->unique()->diff(array_keys($users))->all();
            if ($need) {
                foreach (User::with('agnName')->whereIn('id', $need)->get() as $u) {
                    $users[$u->id] = $u;
                }
                foreach ($need as $id) {
                    $users[$id] ??= null;
                }
            }

            foreach ($rows as $t) {
                $cid = $t->course_id ?: ($quizCourse[$t->quiz] ?? null);
                $user = $users[$t->tester] ?? null;
                if (!$cid || !$user) { $skipped++; continue; }

                $key = $user->id . ':' . $cid;
                if (isset($existing[$key])) { $skipped++; continue; }

                if (!array_key_exists($cid, $courses)) {
                    $courses[$cid] = course::find($cid);
                }
                $course = $courses[$cid];
                if (!$course) { $skipped++; continue; }

                $tk = $cid . ':' . $user->agency;
                if (!array_key_exists($tk, $tplCache)) {
                    $tplCache[$tk] = $this->findTemplate($course, $user);
                }
                $tpl = $tplCache[$tk];
                if (!$tpl) { $skipped++; continue; }

                $percent = $t->totalScore > 0 ? round($t->score / $t->totalScore * 100, 2) : 0;
                $cur = $pending[$key] ?? null;
                if (!$cur) {
                    $pending[$key] = [
                        'user' => $user, 'course' => $course, 'tpl' => $tpl, 'percent' => $percent, 'row' => $t,
                        'issued_at' => $t->end ? Carbon::parse($t->end) : now(),
                    ];
                } elseif ($percent > $cur['percent']) {
                    $pending[$key]['percent'] = $percent;   // keep first-pass date, upgrade score
                    $pending[$key]['row'] = $t;
                }
            }
        }, 'tests.id', 'id');

        // Cert numbers: read the last sequence once per prefix, then count up in memory.
        $seq = [];
        $now = now();
        $issued = 0;
        foreach (array_chunk($pending, 500, true) as $batch) {
            $insert = [];
            foreach ($batch as $p) {
                $agnOf = $p['course']->agn ?: $p['user']->agency;
                $prefix = sprintf('SH-%s-%s-', $agnOf, $p['issued_at']->format('y'));
                if (!isset($seq[$prefix])) {
                    $last = DB::table('certificates')->where('cert_no', 'like', $prefix . '%')->max('cert_no');
                    $seq[$prefix] = $last ? (int) substr($last, strlen($prefix)) : 0;
                }
                $seq[$prefix]++;
                $t = $p['row'];
                $insert[] = [
                    'cert_no' => $prefix . str_pad($seq[$prefix], 5, '0', STR_PAD_LEFT),
                    'user_id' => $p['user']->id,
                    'course_id' => $p['course']->id,
                    'quiz_id' => $t->quiz,
                    'test_id' => $t->id,
                    'template_id' => $p['tpl']->id,
                    'score' => $t->score,
                    'total_score' => $t->totalScore,
                    'percent' => $p['percent'],
                    'issued_at' => $p['issued_at'],
                    'snapshot' => json_encode([
                        'name' => $p['user']->name,
                        'course' => $p['course']->title,
                        'quiz' => $t->quiz_title,
                        'agency' => optional($p['user']->agnName)->name,
                    ]),
                    'agn' => $agnOf,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // insertOrIgnore: a concurrent issue of the same user+course just wins, no crash.
            $issued += DB::table('certificates')->insertOrIgnore($insert);
        }

        return compact('issued', 'skipped');
    }

    /** Data handed to the PDF view for a real certificate. */
    public function dataFor(Certificate $cert): array
    {
        $snap = $cert->snapshot ?? [];
        return [
            'template' => $cert->template,
            'values' => [
                'name' => $snap['name'] ?? optional($cert->user)->name,
                'course' => $snap['course'] ?? optional($cert->course)->title,
                'date' => ($cert->issued_at ?? now())->copy()->locale('en')->translatedFormat('j F Y'),
                'cert_no' => $cert->cert_no,
            ],
        ];
    }

    public function sampleData(CertificateTemplate $template): array
    {
        return [
            'template' => $template,
            'values' => [
                'name' => 'สมชาย ใจดี (Somchai Jaidee)',
                'course' => optional($template->getCourse)->title ?? 'ชื่อหลักสูตรตัวอย่าง',
                'date' => now()->locale('en')->translatedFormat('j F Y'),
                'cert_no' => 'SH-' . $template->agn . '-' . now()->format('y') . '-00001',
            ],
        ];
    }

    public function render(array $data)
    {
        // dompdf registers @font-face fonts into config('dompdf.options.font_dir'); it must exist.
        File::ensureDirectoryExists(storage_path('fonts'));

        // Don't call setOptions(): laravel-dompdf replaces (not merges) config/dompdf.php, which
        // would move the font cache into vendor/ and drop the chroot.
        return Pdf::loadView('page.certificates.pdf', $data + ['pageW' => self::PAGE_W, 'pageH' => self::PAGE_H])
            ->setPaper('a4', 'landscape');
    }
}
