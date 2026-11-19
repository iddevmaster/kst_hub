<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Sso\SsoLoginRejected;
use App\Services\Sso\SsoUserProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    public function getLogin(Request $request) {
        $request->session()->put("state", $state = Str::random(40));
        $query = http_build_query([
            "client_id" => config('auth.client_id'),
            "redirect_uri" => config('auth.callback'),
            "response_type" => "code",
            "scope" => config('auth.scopes'),
            "state" => $state,
        ]);
        return redirect(config('auth.sso_host') . "/oauth/authorize?" . $query);
    }

    public function getCallback(Request $request) {
        $expected = (string) $request->session()->pull("state", '');
        if ($expected === '' || !hash_equals($expected, (string) $request->query('state', ''))) {
            return $this->fail();
        }
        $code = $request->query('code');
        if (!is_string($code) || $code === '') {
            return $this->fail();
        }

        $response = Http::asForm()->acceptJson()->post(config('auth.sso_host') . "/oauth/token", [
            "grant_type" => "authorization_code",
            "client_id" => config('auth.client_id'),
            "client_secret" => config('auth.client_secret'),
            "redirect_uri" => config('auth.callback'),
            "code" => $code,
        ]);
        $access = $response->json('access_token');
        if (!$response->successful() || !is_string($access) || $access === '') {
            return $this->fail();
        }
        $request->session()->put('access_token', $access);
        $request->session()->put('refresh_token', $response->json('refresh_token'));
        $request->session()->put('sso', ['source' => 'idrives']);
        return redirect(route('sso.connect'));
    }

    public function connectUser(Request $request, SsoUserProvisioner $provisioner) {
        try {
            $response = Http::acceptJson()->withToken((string) $request->session()->get("access_token"))
                ->get(config('auth.sso_host') . "/api/user");
            $userArray = $response->json();
            if (!$response->successful() || !is_array($userArray) || !is_string($userArray['username'] ?? null)) {
                return $this->fail();
            }

            if (($userArray['role'] ?? null) === 'staff') {
                // Shared staff account: known issue K4, out of scope for v1.
                $user = User::where('username', 'iddriverstaff')->first();
                if (!$user) {
                    return $this->fail();
                }
            } else {
                $user = $provisioner->provisionIdrives($userArray);
            }

            Auth::login($user);
            $request->session()->put('sso', ['source' => 'idrives']);
            return redirect(route('main'));
        } catch (SsoLoginRejected $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable $th) {
            Log::error('IDDrives SSO connect failed', ['exception' => get_class($th)]);
            return $this->fail('Failed to connect to SSO Server!');
        }
    }

    private function fail(string $message = 'Failed to get login information! Try again.') {
        return redirect(config('auth.sso_host') . '?login_sso_error=' . urlencode($message));
    }
}
