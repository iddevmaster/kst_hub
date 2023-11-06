<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\agency;
use App\Models\branch;
use App\Models\lesson;
use App\Models\department;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\course;

class HomeController extends Controller
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
    public function courseDetail(Request $request, $id) {
        $lessons = lesson::where("course", $id)->get();
        $course = Course::find($id);
        return view("page.course-detail", compact("id", "lessons", "course"));
    }

    public function allCourse(Request $request) {
        $courses = course::all();
        $dpms = department::all();
        return view("page.allcourse", compact("courses", "dpms"));
    }

    public function main(Request $request) {
        $courses = course::latest()->take(6)->get();
        $dpms = department::all();
        if ($request->user()->role == "new") {
            return redirect()->route('home');
        } else {
            return view("page.main", compact("courses", "dpms"));
        }
    }

    public function home(Request $request) {
        $courses = course::latest()->take(6)->where("studens", 'LIKE' , '%"'.$request->user()->id.'"%')->get();
        $user = $request->user();
        return view("page.home", compact("courses", 'user'));
    }

    public function allUsers(Request $request) {
        $users = User::all();
        $agns = agency::all();
        $dpms = department::all();
        $brns = branch::all();
        $roles = Role::all();
        $permissions = Permission::all();
        $courses = course::all();
        return view("page.allusers", compact("users","dpms","agns","brns", "roles", "permissions", "courses"));
    }

    public function userDetail(Request $request, $id) {
        $user = User::find($id);
        $agns = agency::all();
        $dpms = department::all();
        $brns = branch::all();
        $roles = Role::all();
        $permissions = Permission::all();
        $courses = course::all();
        $ucourse = course::whereIn("id", $user->courses ?? [])->get();
        return view("page.userDetail", compact("id","user", "roles", "permissions","dpms","agns","brns", "courses", 'ucourse'));
    }

    public function requestAll(Request $request) {
        return view("page.requestAll");
    }
    public function ownCourse(Request $request) {
        $courses = course::where("teacher", $request->user()->id)->get();

        return view("page.own-course", compact("courses"));
    }

    public function classroom(Request $request) {
        $courses = course::where("studens", 'LIKE' , '%"'.$request->user()->id.'"%')->get();
        $dpms = department::all();
        return view("page.myclassroom", compact("courses","dpms"));
    }
}
