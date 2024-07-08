<?php

namespace App\Console\Commands;

use App\Models\course;
use App\Models\course_has_quiz;
use App\Models\quiz;
use App\Models\user_has_course;
use Illuminate\Console\Command;

class DatabaseNormalize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Update course has quiz
        echo "\ncourse has quiz Updating........\n";
        try {
            $quizzes = quiz::all(['id', 'for_courses']);
            foreach ($quizzes as $quiz) {
                $quiz_course = $quiz->for_courses;
                foreach ($quiz_course as $key => $course) {
                    $course_id = course::where('code', $course)->first(['id']);
                    if (!course_has_quiz::where('course_id', $course_id->id)->where('quiz_id', $quiz->id)->exists()) {
                        course_has_quiz::create([
                            'course_id' => $course_id->id,
                            'quiz_id' => $quiz->id
                        ]);
                        echo "course " . $course_id->id . " has quiz " . $quiz->id . " has been added !!!\n";
                    } else {
                        echo "course " . $course_id->id . " quiz " . $quiz->id . " already exists.\n";
                    }
                }
            }
            echo "course has quiz Update success!!!\n";
        } catch (\Throwable $th) {
            //throw $th;
            echo "error : " . $th->getMessage() . "\n";
        }
        echo "\n";

        // Update user has course
        echo "\n user has course Updating........\n";
        try {
            $courses = course::all(['id', 'studens']);
            foreach ($courses ?? [] as $course) {
                $course_student = $course->studens;
                foreach ($course_student ?? [] as $key => $student) {
                    if (!user_has_course::where('user_id', $key)->where('course_id', $course->id)->exists()) {
                        user_has_course::create([
                            'user_id' => $key,
                            'course_id' => $course->id
                        ]);
                        echo "user " . $key . " has course " . $course->id . " has been added !!!\n";
                    } else {
                        echo "user " . $key . " course " . $course->id . " already exists.\n";
                    }
                }
            }
            echo "user has course Update success!!!\n";
        } catch (\Throwable $th) {
            //throw $th;
            echo "error : " . $th->getMessage() . "\n";
        }
        echo "\n";
    }
}
