<?php

namespace App\Http\Controllers;

use App\Models\agency;
use App\Models\course_group;
use App\Models\SsoCourseMap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SsoCourseMapController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->canManageSubscriptionSso(), 403);
    }

    private function subscriptionAgency(): agency
    {
        return agency::where('agn_id', config('services.sso_subscription.agency'))->firstOrFail();
    }

    public function index()
    {
        $this->authorizeAdmin();
        $agency = $this->subscriptionAgency();
        $maps = SsoCourseMap::where('source', 'subscription')->orderBy('code')->get();
        $groups = course_group::where('agn', (string) $agency->id)->orderBy('name')->get(['id', 'name', 'code', 'by']);
        return view('page.sso.course-maps', compact('maps', 'groups'));
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAdmin();
        $agency = $this->subscriptionAgency();
        $data = $request->validate([
            'course_group_id' => ['nullable', 'integer',
                Rule::exists('course_groups', 'id')->where('agn', (string) $agency->id)],
        ]);
        SsoCourseMap::where('source', 'subscription')->findOrFail($id)
            ->update(['course_group_id' => $data['course_group_id'] ?? null]);
        return back()->with('success', __('messages.sso_map_saved'));
    }
}
