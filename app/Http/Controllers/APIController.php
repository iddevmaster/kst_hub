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
            'org' => 'required|string|max:500',
            // Add other fields as necessary
        ]);
        if (User::where('username', $request->username)->exists()) {
            $user = User::where('username', $request->username)->first();
        } else {
            $tsmcadmin = User::where('username', 'tsmcschool')->first();

            if (branch::where('name', $request->org)->exists()) {
                $branch = branch::where('name', $request->org)->first();
            } else {
                $branch = branch::create([
                    'name' => $request->org,
                    'agency' => $tsmcadmin->agency,
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'password' => $request->username,
                'agency' => $tsmcadmin->agency,
                'brn' => $branch->id,
                'dpm' => '',
                'role' => 'employee',
                'courses' => [],
                'startlt' => null,
            ]);
        }
        Auth::login($user);
        return redirect()->route('course.detail', ['id' => $courseId]);
    }
}
