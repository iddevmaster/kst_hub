<?php

namespace App\Http\Controllers;

use App\Models\Activitylog;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\course;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin side of certificates: templates (background upload + course mapping + field positions)
 * and the list of issued certificates.
 */
class CertificateTemplateController extends Controller
{
    private function authorizeAdmin(): void
    {
        $u = auth()->user();
        abort_unless($u->can('quiz') || $u->hasAnyRole(['admin', 'superAdmin']), 403);
    }

    private function isSuper(): bool
    {
        return auth()->user()->hasRole('superAdmin');
    }

    private function scopeAgn($query, string $col = 'agn')
    {
        return $this->isSuper() ? $query : $query->where($col, auth()->user()->agency);
    }

    private function findOwned($id): CertificateTemplate
    {
        return $this->scopeAgn(CertificateTemplate::query())->findOrFail($id);
    }

    private function courses()
    {
        return $this->scopeAgn(course::query())->orderBy('title')->get(['id', 'title', 'code', 'agn']);
    }

    // ---------- templates ----------

    public function index()
    {
        $this->authorizeAdmin();
        $templates = $this->scopeAgn(CertificateTemplate::with('getCourse'))->orderByDesc('id')->get();
        return view('page.certificates.templates', compact('templates'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('page.certificates.template_form', [
            'template' => null,
            'courses' => $this->courses(),
            'fields' => CertificateTemplate::defaultFields(),
        ]);
    }

    public function edit($id)
    {
        $this->authorizeAdmin();
        $template = $this->findOwned($id);
        return view('page.certificates.template_form', [
            'template' => $template,
            'courses' => $this->courses(),
            'fields' => $template->resolvedFields(),
        ]);
    }

    private function rules(bool $creating): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'course_id' => 'nullable|integer',
            'bg_image' => ($creating ? 'required' : 'nullable') . '|image|mimes:jpg,jpeg,png|max:8192',
            'active' => 'nullable|boolean',
        ];
        foreach (CertificateTemplate::FIELD_KEYS as $k) {
            $rules["fields.$k.show"] = 'nullable|boolean';
            $rules["fields.$k.bold"] = 'nullable|boolean';
            $rules["fields.$k.x"] = 'required|numeric|between:0,100';
            $rules["fields.$k.y"] = 'required|numeric|between:0,100';
            $rules["fields.$k.size"] = 'required|numeric|between:6,120';
            $rules["fields.$k.align"] = ['required', Rule::in(['left', 'center', 'right'])];
        }
        return $rules;
    }

    private function cleanFields(array $input): array
    {
        $out = [];
        foreach (CertificateTemplate::FIELD_KEYS as $k) {
            $f = $input[$k] ?? [];
            $out[$k] = [
                'show' => !empty($f['show']),
                'bold' => !empty($f['bold']),
                'x' => (float) $f['x'],
                'y' => (float) $f['y'],
                'size' => (float) $f['size'],
                'align' => $f['align'],
            ];
        }
        return $out;
    }

    private function saveBackground(Request $request): ?string
    {
        if (!$request->hasFile('bg_image')) {
            return null;
        }
        $file = $request->file('bg_image');
        $dir = public_path('uploads/certificates');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        // extension() is derived from the file's real MIME type, not the client-supplied name.
        $name = uniqid('bg_') . '.' . $file->extension();
        $file->move($dir, $name);
        return $name;
    }

    /** Agency + course this template applies to, checked against what the current user may touch. */
    private function resolveCourse(Request $request): array
    {
        $courseId = $request->filled('course_id') ? (int) $request->course_id : null;
        if ($courseId) {
            $c = $this->scopeAgn(course::query())->findOrFail($courseId);
            return [$c->id, $c->agn];
        }
        return [null, auth()->user()->agency];
    }

    private function assertNoActiveDuplicate(?int $courseId, string $agn, ?int $ignoreId, bool $active): void
    {
        if (!$active) {
            return;
        }
        $dup = CertificateTemplate::where('active', true)->where('agn', $agn)
            ->where('course_id', $courseId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($dup) {
            throw ValidationException::withMessages([
                'course_id' => 'มี template ที่เปิดใช้งานสำหรับ' . ($courseId ? 'หลักสูตรนี้' : 'ค่าเริ่มต้น') . 'อยู่แล้ว กรุณาปิดอันเดิมก่อน',
            ]);
        }
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate($this->rules(true));
        [$courseId, $agn] = $this->resolveCourse($request);
        $active = $request->boolean('active');
        $this->assertNoActiveDuplicate($courseId, $agn, null, $active);

        $tpl = CertificateTemplate::create([
            'name' => $data['name'],
            'agn' => $agn,
            'course_id' => $courseId,
            'bg_image' => $this->saveBackground($request),
            'fields' => $this->cleanFields($data['fields']),
            'active' => $active,
            'created_by' => auth()->id(),
        ]);
        $this->log('create template ' . $tpl->id);

        return redirect()->route('certificates.templates.edit', $tpl->id)->with('success', 'บันทึก template แล้ว');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();
        $tpl = $this->findOwned($id);
        $data = $request->validate($this->rules(false));
        [$courseId, $agn] = $this->resolveCourse($request);
        $active = $request->boolean('active');
        $this->assertNoActiveDuplicate($courseId, $agn, $tpl->id, $active);

        $attrs = [
            'name' => $data['name'],
            'agn' => $agn,
            'course_id' => $courseId,
            'fields' => $this->cleanFields($data['fields']),
            'active' => $active,
        ];
        if ($bg = $this->saveBackground($request)) {
            $attrs['bg_image'] = $bg;
        }
        $tpl->update($attrs);
        $this->log('update template ' . $tpl->id);

        return redirect()->route('certificates.templates.edit', $tpl->id)->with('success', 'บันทึก template แล้ว');
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();
        $tpl = $this->findOwned($id);
        $tpl->delete(); // soft delete: already-issued certificates keep rendering with it
        $this->log('delete template ' . $tpl->id);
        return redirect()->route('certificates.templates')->with('success', 'ลบ template แล้ว');
    }

    public function preview($id, CertificateService $svc)
    {
        $this->authorizeAdmin();
        $tpl = $this->findOwned($id);
        return $svc->render($svc->sampleData($tpl))->stream('certificate_preview.pdf');
    }

    // ---------- issued certificates ----------

    public function manage(Request $request)
    {
        $this->authorizeAdmin();
        $q = $this->scopeAgn(Certificate::with(['user', 'course']))->orderByDesc('id');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                // snapshot JSON stores Thai as \uXXXX escapes, so search the live user/course names instead.
                $w->where('cert_no', 'like', "%$s%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%$s%"));
            });
        }
        $certs = $q->paginate(15)->withQueryString();
        return view('page.certificates.manage', compact('certs'));
    }

    public function toggleRevoke($id)
    {
        $this->authorizeAdmin();
        $cert = $this->scopeAgn(Certificate::query())->findOrFail($id);
        $cert->update(['revoked_at' => $cert->isRevoked() ? null : now()]);
        $this->log(($cert->isRevoked() ? 'revoke' : 'restore') . ' certificate ' . $cert->id);
        return back()->with('success', $cert->isRevoked() ? 'เพิกถอนใบรับรองแล้ว' : 'คืนสถานะใบรับรองแล้ว');
    }

    public function backfill(CertificateService $svc)
    {
        $this->authorizeAdmin();
        $res = $svc->backfill($this->isSuper() ? null : auth()->user()->agency);
        $this->log('backfill certificates issued=' . $res['issued']);
        return back()->with('success', "ออกใบรับรองย้อนหลังแล้ว {$res['issued']} ใบ (ข้าม {$res['skipped']} รายการ)");
    }

    private function log(string $note): void
    {
        Activitylog::create([
            'user' => auth()->id(),
            'module' => 'Certificate',
            'content' => $note,
            'note' => 'cert',
            'agn' => auth()->user()->agency,
        ]);
    }
}
