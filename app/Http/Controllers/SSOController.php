<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\agency;
use App\Models\branch;
use App\Models\department;
use App\Models\User;
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

        throw_unless(strlen($state) > 0 && $state == $request->state , InvalidArgumentException::class);

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

                $agn = agency::where('name', $userArray['agency'])->first();
                if ($agn) {
                    $user->agency = $agn->id;
                } else {
                    $agn = agency::create(['name' => $userArray['agency']]);
                    $user->agency = $agn->id;
                }

                $brn = branch::where('name', $userArray['branch'])->first();
                if ($brn) {
                    $user->brn = $brn->id;
                } else {
                    $brn = new branch;
                    $brn->name = $userArray['branch'];
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
            }
            $user->course_group = $userArray['course_code'];
            $user->save();
            Auth::login($user);
            return redirect(route('main'));
        } catch (\Throwable $th) {
            return redirect('/')->with('error', 'Failed to connect to SSO Server!');
        }
    }
}
