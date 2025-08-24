<?php

namespace App\Http\Controllers;

use App\Models\course;
use Illuminate\Http\Request;

class APIController extends Controller
{
    public function getAgnCourse($agnId)
    {
        $courses = course::where('agn', $agnId)->orderByDesc('created_at')->limit(6)->get(['id', 'title', 'description', 'code', 'img']);
        return response()->json($courses);
    }
}
