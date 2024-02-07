<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Activitylog;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        Log::channel('activity')->info('User '. $request->user()->name .' visited user profile',
        [
            'user' => $request->user(),
        ]);
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = User::find($request->user()->id);
        try {
            $user->name = $request->name;
            $user->username = $request->email;
            $user->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Profile',
                'content' => $user->id,
                'note' => 'Update',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update user profile',
            [
                'user' => $request->user(),
                'user_update' => $user,
            ]);
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Throwable $th) {
            //throw $th;
            return Redirect::route('profile.edit')->with('status', 'profile-error');
        }
    }

    public function updateIcon(ProfileUpdateRequest $request): RedirectResponse
    {
        if ($request->action == '1') {
            try {
                $fileName = time() . '.' . $request->icon->extension();
                $request->icon->move(public_path('img/icons'), $fileName);
                $request->user()->fill(['icon' => $fileName]);
                $request->user()->save();

                Activitylog::create([
                    'user' => auth()->id(),
                    'module' => 'Icon',
                    'content' => $request->user()->id,
                    'note' => 'Update',
                    'agn' => auth()->user()->agency
                ]);
                Log::channel('activity')->info('User '. $request->user()->name .' updated user icon',
                [
                    'user' => $request->user(),
                ]);
                return Redirect::route('profile.edit')->with('status', 'profile-updated');
            } catch (\Throwable $th) {
                return Redirect::route('profile.edit')->with('status', 'profile-updated-error');
            }
        } elseif ($request->action == '0') {
            if ($request->user()->icon !== 'person.jpg') {
                $pathToFile = public_path('img/icons/'. $request->user()->icon);
                if (File::exists($pathToFile)) {
                    File::delete($pathToFile);
                }
            }
            $request->user()->fill(['icon' => 'person.jpg']);
            $request->user()->save();
            return Redirect::route('profile.edit')->with('status', 'icon-deleted');
        }
    }
    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();


        Log::channel('activity')->info('User '. $request->user()->name .' delete user account',
        [
            'user' => $request->user(),
            'delete_user' => $user,
        ]);
        return Redirect::to('/');
    }
}
