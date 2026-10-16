<?php

namespace App\Http\Controllers;

use App\Models\branch;
use App\Models\course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class APIController extends Controller
{
    public function getAgnCourse($agnId)
    {
        $courses = course::where('agn', $agnId)->orderByDesc('created_at')->limit(6)->get(['id', 'title', 'description', 'code', 'img']);
        return response()->json($courses);
    }

    public function fromTsmc(Request $request, $courseId) {

        $request->validate([
            'name' => 'required|string|max:500',
            'username' => 'required|string|max:500',
            'org' => $request->is_tsm === '1' ? 'nullable|string|max:500' : 'required|string|max:500',
            'is_tsm' => 'required|string|max:5',
            // Add other fields as necessary
        ]);
        if (User::where('username', $request->username)->exists()) {
            $user = User::where('username', $request->username)->first();
        } else {
            $tsmcadmin = User::where('username', 'tsmcschool')->first();

            if ($request->is_tsm === '0') {
                if (branch::where('name', $request->org)->exists()) {
                    $branch = branch::where('name', $request->org)->first();
                } else {
                    $branch = branch::create([
                        'name' => $request->org,
                        'agency' => $tsmcadmin->agency,
                    ]);
                }
            }

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'password' => $request->username,
                'agency' => $tsmcadmin->agency,
                'brn' => $request->is_tsm === '1' ? $tsmcadmin->brn : $branch->id,
                'dpm' => '',
                'role' => 'TSMCStudent',
                'courses' => [],
                'startlt' => null,
            ]);
        }
        Auth::login($user);
        return redirect()->route('course.detail', ['id' => $courseId]);
    }


    public function fromPublic() {

        $user = User::where('username', 'publicstudent')->firstOrFail();
        Auth::login($user);
        return redirect()->route('main');
    }
}
