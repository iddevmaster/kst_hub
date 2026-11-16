<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;

class MyCertificateController extends Controller
{
    public function index()
    {
        $certs = Certificate::with(['course', 'template'])
            ->where('user_id', auth()->id())
            ->orderByDesc('issued_at')->get();
        return view('page.certificates.mine', compact('certs'));
    }

    public function download($id, CertificateService $svc)
    {
        $cert = Certificate::findOrFail($id);
        $u = auth()->user();

        $isOwner = $cert->user_id == $u->id;
        $isAdmin = $u->hasRole('superAdmin')
            || (($u->hasRole('admin') || $u->can('quiz')) && $cert->agn == $u->agency);
        abort_unless($isOwner || $isAdmin, 403);
        abort_if($cert->isRevoked() && !$isAdmin, 403, 'ใบรับรองนี้ถูกเพิกถอน');
        abort_unless($cert->template && is_file($cert->template->bgPath()), 404, 'ไม่พบไฟล์ template ของใบรับรอง');

        return $svc->render($svc->dataFor($cert))->stream('Certificate_' . $cert->cert_no . '.pdf');
    }
}
