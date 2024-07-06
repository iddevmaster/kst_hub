<?php

namespace App\Http\Controllers;

use App\Models\course;
use App\Models\course_has_quiz;
use App\Models\quiz;
use App\Models\Test;
use App\Models\User;
use App\Models\user_has_course;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(['name' => 'index']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['name' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json(['name' => 'store', 'payload' => $request->all()]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = User::where('id', $id)->first();
            if (is_null($user)) {
                return response()->json(['message' => 'User id ' . $id . ' has not exist.'], 400);
            } else {
                $course_list = user_has_course::where('user_id', $user->id)->get(['course_id']);
                $courses = course::whereIn("id", $course_list)->get();
                $user_data = [
                    "id" => $user->id,
                    "name" => $user->name,
                    "organization" => optional($user->agnName)->name,
                    "branch" => optional($user->getBrn)->name,
                    "department" => optional($user->dpmName)->name,
                ];

                $course_data = [];
                foreach ($courses as $course) {
                    $quiz_list = course_has_quiz::where('course_id', $course->id)->get(['quiz_id']);
                    $quizzes = quiz::whereIn('id', $quiz_list)->get();

                    $test_data = [];
                    foreach ($quizzes as $quiz) {
                        $test = Test::where('quiz', $quiz->id)->where('tester', $id)->orderByDesc('score')->first();
                        $test_data[] = [
                            "quiz_name" => $quiz->title,
                            "test_score" => $test->score,
                            "total_test_score" => $test->totalScore,
                            "test_date" => $test->start
                        ];
                    }

                    $course_data[] = [
                        "course_code" => $course->code,
                        "course_name" => $course->title,
                        "test_history" => $test_data
                    ];
                }
                return response()->json(['user' => $user_data, 'courses' => $course_data]);
            }

        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return response()->json(['name' => 'edit', 'id' => $id]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json(['name' => 'update', 'payload' => $request->all(), 'id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json(['name' => 'destroy', 'id' => $id]);
    }
}
