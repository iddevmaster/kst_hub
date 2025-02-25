<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\course;
use App\Models\progress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
Use Alert;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Activitylog;
use App\Models\course_group;
use App\Models\course_has_group;
use App\Models\user_has_course;
use App\Models\user_has_group;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
            'agn' => ['required', 'string', 'max:255'],
            'brn' => ['required', 'string', 'max:255'],
            'dpm' => ['string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'The name field is mandatory!',
            'username.required' => 'The username field is mandatory!',
            'agn.required' => 'The agency field is mandatory!',
            'brn.required' => 'The branch field is mandatory!',
            'role.required' => 'The role field is mandatory!',

        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'password' => $request->password,
                'agency' => $request->agn,
                'brn' => $request->brn,
                'dpm' => $request->dpm ?? '',
                'role' => $request->role,

                'courses' => $request->courses ?? [],
                'startlt' => ($request->role == 'new' ? date('Y-m-d') : null),
            ]);
            foreach (Role::all() as $role) {
                if ($user->hasRole($role->name)) {
                    $user->removeRole($role->name);
                }
            }
            $user->assignRole($request->role);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'User',
                'content' => $user->id,
                'note' => 'create',
                'agn' => $user->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' create new User',
            [
                'user' => $request->user(),
                'user_create' => $user,
            ]);
            alert()->success('Success','User has been created!');
        } catch (\Throwable $th) {
            alert()->error('Some thing worng!', $th->getMessage());
        }
        return Redirect::route('users.all');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255'],
                'agn' => ['required', 'string', 'max:255'],
                'brn' => ['required', 'string', 'max:255'],
                'dpm' => ['string', 'max:255'],
                'role' => ['required', 'string', 'max:255'],
            ], [
                'name.required' => 'The name field is mandatory!',
                'username.required' => 'The username field is mandatory!',
                'agn.required' => 'The agency field is mandatory!',
                'brn.required' => 'The branch field is mandatory!',
                'role.required' => 'The role field is mandatory!',
            ]);

            $user = User::find($request->uid);
            $user->name = $request->name;
            $user->username = $request->username;
            if (strlen($request->password) >= 8) {
                $user->password = $request->password;
            }
            $user->agency = $request->agn;
            $user->brn = $request->brn;
            $user->dpm = $request->dpm ?? '';
            $user->role = $request->role;
            $user->startlt = ($request->role == 'new' ? date('Y-m-d') : null);
            foreach (Role::all() as $role) {
                if ($user->hasRole($role->name)) {
                    $user->removeRole($role->name);
                }
            }
            $user->assignRole($request->role);
            $user->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'User',
                'content' => $user->id,
                'note' => 'update',
                'agn' => $user->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update user',
            [
                'user' => $request->user(),
                'user_update' => $user,
            ]);
            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $user = User::find( $request->delid )->delete();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'User',
                'content' => $user->id,
                'note' => 'delete',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' delete user',
            [
                'user' => $request->user(),
                'user_destroy' => $user,
            ]);
            return response()->json(['success' => $request->all() ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function renew(Request $request)
    {
        try {
            $user = User::find( $request->uid );
            $user->update(['startlt'=> $request->date]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'User',
                'content' => $user->id,
                'note' => 'renew',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' renew user',
            [
                'user' => $request->user(),
                'user_renew' => $user,
            ]);
            return response()->json(['success' => $request->all() ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function addCourse(Request $request) {
        try {
            foreach ($request->courses ?? [] as $cid) {
                if (!user_has_course::where('user_id', $request->uid)->where('course_id', $cid)->exists()) {
                    user_has_course::create([
                        'user_id' => $request->uid,
                        'course_id' => $cid
                    ]);
                }
            }

            return response()->json(['success' => $request->courses ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function removeCourse(Request $request) {
        try {
            user_has_course::where('user_id', $request->uid)->where('course_id', $request->cid)->delete();

            return response()->json(['success' => $request->all() ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function addProgress(Request $request) {
        try {
            $cid = $request->query('cid');
            $lessid = $request->query('lessid');
            $userId = $request->user()->id;

            // Check if the progress already exists
            $progressExists = progress::where('course_id', $cid)
                                      ->where('lesson_id', $lessid)
                                      ->where('user_id', $userId)
                                      ->exists();

            if (!$progressExists) {
                // Only create a new record if it does not exist
                progress::create(['course_id' => $cid, 'lesson_id' => $lessid, 'user_id' => $userId, 'agn' => auth()->user()->agency]);
                return response()->json(['message' => 'Progress added']);
            } else {
                // Return a message indicating that the progress already exists
                return response()->json(['message' => 'Progress already exists']); // 409 Conflict
            }

        } catch (\Throwable $th) {
            // Handle exceptions and return an error message
            return response()->json(['message' => $th->getMessage()], 500); // 500 Internal Server Error
        }
    }


    public function addGroup(Request $request) {
        try {
            if (!user_has_group::where('user_id', $request->uid)->where('group_id', $request->groups)->exists()) {
                user_has_group::create([
                    'user_id' => $request->uid,
                    'group_id' => $request->groups
                ]);
            }

            $courses = course_has_group::whereIn('group_id', $request->groups)->get(['course_id']);
            foreach ($courses as $course) {
                if (!user_has_course::where('user_id', $request->uid)->where('course_id', $course->course_id)->exists()) {
                    user_has_course::create([
                        'user_id' => $request->uid,
                        'course_id' => $course->course_id
                    ]);
                }
            }

            return response()->json(['success' => $request->all()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

}
