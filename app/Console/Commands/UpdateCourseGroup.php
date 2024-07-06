<?php

namespace App\Console\Commands;

use App\Models\agency;
use App\Models\course_group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateCourseGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:coursegroup';

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
    $url = config('auth.sso_host') . "/api/courseType";

    // Fetch data from the API
    $response = Http::get($url);

    // Check if the request was successful
    if ($response->successful()) {
        $datas = $response->json();
        $agn = agency::where('agn_id', "IDD0002")->orWhere('name', "โรงเรียนสอนขับรถ ไอดี ไดร์ฟเวอร์")->first();
        if (!$agn) {
            $agn = agency::create(['name' => "โรงเรียนสอนขับรถ ไอดี ไดร์ฟเวอร์", 'agn_id' => "IDD0002"]);
        }
        foreach ($datas as $key => $data) {
            $course_g = course_group::where('code', $data['code'])->first();
            if (!$course_g) {
                $course_g = course_group::create([
                    "name" => $data['name'],
                    "agn" => $agn->id,
                    "by" => "api:sso",
                    "code" => $data['code']
                ]);
            } else {
                $course_g->name = $data['name'];
                $course_g->save();
            }
            echo $data['code'] . ' ' . $data['name'] . "\n";
        }
        echo "Data fetched successfully\n";
        // Process your data here
    } else {
        $this->error('Failed to fetch data from ' . $url);
    }
}
}
