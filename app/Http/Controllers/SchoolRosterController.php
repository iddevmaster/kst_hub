<?php

namespace App\Http\Controllers;

use App\Models\agency;
use App\Models\branch;
use App\Models\User;
use App\Services\Sso\SubscriptionRosterClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SchoolRosterController extends Controller
{
    public function index(Request $request, SubscriptionRosterClient $client)
    {
        abort_unless($request->user()->canManageSubscriptionSso(), 403);
        $agency = agency::where('agn_id', config('services.sso_subscription.agency'))->firstOrFail();
        $schools = branch::where('agency', $agency->id)->where('source', 'subscription')->orderBy('name')->get();

        $school = $request->filled('school') ? $schools->firstWhere('id', (int) $request->query('school')) : null;
        $schoolBranches = [];
        $users = collect();
        $active = [];
        $error = null;

        try {
            if ($school) {
                $schoolBranches = $client->branches($school->brn_id);
            }
            if ($school && $request->filled('branch')) {
                $active = $client->roster($school->brn_id, (string) $request->query('branch'));
                foreach (array_chunk(array_keys($active), 1000) as $ids) {
                    $users = $users->merge(User::where('agency', (string) $agency->id)
                        ->where('brn', (string) $school->id)->whereIn('id_number', $ids)->orderBy('name')->get());
                }
            }
        } catch (RequestException $e) {
            Log::warning('Roster request failed', ['status' => $e->response?->status()]);
            $error = __('messages.sso_roster_error');
        } catch (ConnectionException $e) {
            Log::warning('Roster request failed: connection error');
            $error = __('messages.sso_roster_error');
        }

        return view('page.sso.roster', compact('schools', 'school', 'schoolBranches', 'users', 'active', 'error'));
    }
}
