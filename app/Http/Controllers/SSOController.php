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

            $agn = agency::where('name', 'TrainingZenter')->first();
            if ($agn) {
                $user->agency = $agn->id;
            } else {
                $user->agency = '';
            }

            $brn = branch::where('name', 'TrainingZenter')->first();
            if ($brn) {
                $user->brn = $brn->id;
            } else {
                $user->brn = '';
            }

            $dpm = department::where('name', 'TrainingZenter')->first();
            if ($dpm) {
                $user->dpm = $dpm->id;
            } else {
                $user->dpm = '';
            }

            $user->role = $userArray['role'];
            if (!Role::where('name', $userArray['role'])->exists()) {
                Role::create(['name' => $userArray['role']]);
            }
            $user->assignRole($userArray['role']);
            $user->save();
        }
        $user->course_group = $userArray['course'];
        $user->save();
        Auth::login($user);
        return redirect(route('main'));
    }
}
