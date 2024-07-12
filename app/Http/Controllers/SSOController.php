<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\agency;
use App\Models\branch;
use App\Models\course_group;
use App\Models\department;
use App\Models\User;
use App\Models\user_has_group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class SSOController extends Controller
{
    public function getLogin(Request $request) {
        $request->session()->put("state", $state =  Str::random(40));
        $query = http_build_query([
            "client_id" => config('auth.client_id'),
            "redirect_uri" => config('auth.callback'),
            "response_type" => "code",
            "scope" => config('auth.scopes'),
            "state" => $state,
        ]);
        return redirect( config('auth.sso_host') . "/oauth/authorize?" . $query);
    }

    public function getCallback(Request $request) {
        $state = $request->session()->pull("state");

        // throw_unless(strlen($state) > 0 && $state == $request->state , InvalidArgumentException::class);

        $response = Http::asForm()->post(
            config('auth.sso_host') . "/oauth/token",
            [
                "grant_type" => "authorization_code",
                "client_id" => config('auth.client_id'),
                "client_secret" => config('auth.client_secret'),
                "redirect_uri" => config('auth.callback'),
                "code" => $request->code
            ]
        );
        $request->session()->put($response->json());
        return redirect(route('sso.connect'));
    }

    public function connectUser(Request $request) {
        try {
            $access_token = $request->session()->get("access_token");
            $response = Http::withHeaders([
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $access_token
            ])->get( config('auth.sso_host') . "/api/user");
            $userArray = $response->json();

            try {
                $username = $userArray['username'];
            } catch (\Throwable $th) {
                return redirect('login')->withError("Failed to get login information! Try again.");
            }
            $user = User::where('username', $username)->first();
            if (!$user) {
                $user = new User;
                $user->name = $userArray['name'];
                $user->username = $username;
                $user->password = $username;

                $agn = agency::where('agn_id', $userArray['agency']['agn_id'])->first();
                if ($agn) {
                    $user->agency = $agn->id;
                } else {
                    $agn = agency::create(['name' => $userArray['agency']['name'], 'agn_id' => $userArray['agency']['agn_id']]);
                    $user->agency = $agn->id;
                }
                $brn = branch::where('brn_id', $userArray['branch']['brn_id'])->first();
                if ($brn) {
                    $user->brn = $brn->id;
                } else {
                    $brn = new branch;
                    $brn->name = $userArray['branch']['name'];
                    $brn->brn_id = $userArray['branch']['brn_id'];
                    $brn->agency = $agn->id;
                    $brn->save();
                    $user->brn = $brn->id;
                }

                $user->dpm = '';

                $user->role = $userArray['role'];
                if (!Role::where('name', $userArray['role'])->exists()) {
                    Role::create(['name' => $userArray['role']]);
                }
                if (!$user->hasRole($userArray['role'])) {
                    $user->assignRole($userArray['role']);
                }
            } else {
                $agn = agency::where('agn_id', $userArray['agency']['agn_id'])->orWhere('name', $userArray['agency']['name'])->first();
                if ($agn) {
                    $agn->update(['name' => $userArray['agency']['name'], 'agn_id' => $userArray['agency']['agn_id']]);
                    $user->agency = $agn->id;
                } else {
                    $agn = agency::create(['name' => $userArray['agency']['name'], 'agn_id' => $userArray['agency']['agn_id']]);
                    $user->agency = $agn->id;
                }
                $brn = branch::where('brn_id', $userArray['branch']['brn_id'])->orWhere('name', $userArray['branch']['name'])->first();
                if ($brn) {
                    $brn->update(['name' => $userArray['branch']['name'], 'brn_id' => $userArray['branch']['brn_id'], 'agency' => $agn->id]);
                    $user->brn = $brn->id;
                } else {
                    $brn = new branch;
                    $brn->name = $userArray['branch']['name'];
                    $brn->brn_id = $userArray['branch']['brn_id'];
                    $brn->agency = $agn->id;
                    $brn->save();
                    $user->brn = $brn->id;
                }

            }

            $course_g = course_group::where('code', $userArray['courses'][0]['course_type'])->first();
            if (!$course_g) {
                $course_g = course_group::create([
                    "name" => $userArray['courses'][0]['name'],
                    "agn" => $user->agency,
                    "by" => "api:sso",
                    "code" => $userArray['courses'][0]['course_type']
                ]);
            }

            $user->save();

            $user_group = user_has_group::where('user_id', $user->id)->where('group_id', $course_g->id)->first();
            if (!$user_group) {
                user_has_group::create([
                    'user_id' => $user->id,
                    'group_id' => $course_g->id
                ]);
            }

            Auth::login($user);
            return redirect(route('main'));
        } catch (\Throwable $th) {
            // show error
            return redirect('/')->with('error', 'Failed to connect to SSO Server!' . $th->getMessage());
        }
    }
}
