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
        $issued = 0;
        $skipped = 0;
        $q = Test::query()->orderBy('id');
        if ($agn) {
            $q->where('agn', $agn);
        }
        $q->chunkById(200, function ($tests) use (&$issued, &$skipped) {
            foreach ($tests as $test) {
                $before = Certificate::count();
                $cert = $this->issueForTest($test);
                if ($cert && Certificate::count() > $before) {
                    $issued++;
                } else {
                    $skipped++;
                }
            }
        });
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
