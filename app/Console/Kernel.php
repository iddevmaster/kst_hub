<?php

namespace App\Console;

use App\Models\agency;
use App\Models\course_group;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Http;
use Throwable;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call( function () {
            try {
                $response = Http::withHeaders([
                    "Authorization" => "Basic YWRtaW46aWRkcml2ZXNhZG1pbg=="
                ])->get(config('auth.sso_host') . "/api/courses");

                // Check for successful response
                if ($response->ok()) {
                    echo "Fetch successful! Status code: " . $response->getStatusCode() . "\n";
                    $this->updateCourse($response->json());
                    // Process the response data here (e.g., $data = $response->json())
                } else {
                    echo "Fetch failed with status code: " . $response->getStatusCode() . "\n";
                    // Handle the error (e.g., throw an exception)
                }
            } catch (Throwable $e) {
                echo "An error occurred during the fetch: " . $e->getMessage() . "\n";
                // Handle the exception appropriately
            }
        });
    }

    public function updateCourse ($courses = []): void {
        echo "Updateing..." ."\n";

        // บันทึกข้อมูลคอร์สลงในฐานข้อมูล
        foreach ($courses as $course) {
            try {
                $agn = agency::where('name', $course['agn'])->first();
                $hascourse = course_group::where('code', $course['code'])->orderBy('id', 'desc')->first();
                if ($hascourse) {
                    $hascourse->update([
                        "name" => $course['name'],
                        "agn" => $agn->id,
                        "by" => "api:sso",
                        "code" => $course['code']
                    ]);
                } else {
                    course_group::create([
                        "name" => $course['name'],
                        "agn" => $agn->id,
                        "by" => "api:sso",
                        "code" => $course['code']
                    ]);
                }
                echo "Updating course: " . $course['code'] . ' / ' . $course['agn'] . " Success! \n";
            } catch (\Throwable $th) {
                echo "Updating course: " . $course['code'] . ' / ' . $course['agn'] . " Unsuccess! \n";
            }

        }

        // ลบคอร์สที่ไม่มีในข้อมูล API
        $apiCourseCodes = array_column($courses, 'code');
        course_group::where('by', 'api:sso')->whereNotIn('code', $apiCourseCodes)->delete();

        echo "Update success! \n";
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
