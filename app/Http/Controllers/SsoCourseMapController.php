<?php

namespace App\Http\Controllers;

use App\Models\agency;
use App\Models\course_group;
use App\Models\SsoCourseMap;
use App\Services\Sso\VehicleTypeSync;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
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
        // Groups the vehicle-type sync created: used when a code has no explicit mapping.
        $auto = $groups->where('by', VehicleTypeSync::SOURCE_TAG)->pluck('name', 'code');
        return view('page.sso.course-maps', compact('maps', 'groups', 'auto'));
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

    public function sync(VehicleTypeSync $sync)
    {
        $this->authorizeAdmin();
        try {
            $r = $sync->run();
        } catch (RequestException|ConnectionException $e) {
            Log::warning('Vehicle type sync failed', ['status' => $e instanceof RequestException ? $e->response?->status() : null]);
            return back()->with('sync_error', __('messages.sso_sync_failed'));
        } catch (\RuntimeException) {
            return back()->with('sync_error', __('messages.sso_sync_failed'));
        }
        return back()->with('success', __('messages.sso_sync_done', $r));
    }
}
