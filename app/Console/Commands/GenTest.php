<?php

namespace App\Console\Commands;

use App\Models\Test;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class GenTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gen-test';

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
        $sample_test = Test::whereIn('id', [20907, 20905])->get();
        $this->info('Sample tests retrieved: ' . count($sample_test));

        $sample_answer1 = $sample_test[0]->answers;

        $sample_answer2 = $sample_test[1]->answers;
        $this->info('sample ex: ' . json_encode($sample_answer2) . " \n");

        $merged = $sample_answer1 + $sample_answer2;

        // สุ่มเฉพาะ key
        $randomKeys = Arr::random(array_keys($merged), 30);

        // rebuild array โดยรักษา key เดิม
        $random = collect($randomKeys)
            ->mapWithKeys(function ($key) use ($merged) {
                return [$key => $merged[$key]];
            })
            ->toArray();

        // นับ status = 1
        $count_status = collect($random)
            ->where('status', '1')
            ->count();

        $this->info('Random sample: ' . json_encode($random));
        $this->info('Count of status 1: ' . $count_status);
        $this->info('total: ' . count($random));

        $randomDateTimes = $this->randomDateTimes();

        $users = User::whereBetween('username', ['CP2569041', 'CP2569070'])->whereNot('username', 'CP2569046')->get('id');
        foreach ($users as $index => $user) {
            $startDate = $randomDateTimes[$index % count($randomDateTimes)];
            $endDate = Carbon::parse($startDate)->addSeconds(rand(600, 1800))->format('Y-m-d H:i:s');
            $this->info('startDate: ' . $startDate . '-- endDate: ' . $endDate);

            // $new_test = new Test();
            // $new_test->quiz = $sample_test[0]->quiz;
            // $new_test->course_id = $sample_test[0]->course_id;
            // $new_test->tester = $user->id;
            // $new_test->answers = $random;
            // $new_test->score = $count_status;
            // $new_test->totalScore = count($random);
            // $new_test->agn = $sample_test[0]->agn;
            // $new_test->start = $startDate;
            // $new_test->end = $endDate;
            // $new_test->save();
            // $this->info('Test created for user: ' . $user->username);
        }
    }

    public function randomDateTimes()
    {
        $startDate = Carbon::create(2026, 2, 17, 9, 0, 0);
        $endDate   = Carbon::create(2026, 2, 19, 15, 0, 0);

        $results = [];

        for ($i = 0; $i < 30; $i++) {

            // สุ่มวันระหว่าง 17-19
            $randomDay = Carbon::create(2026, 2, rand(17, 19));

            // สุ่มเวลา 09:00 - 15:00
            $randomHour = rand(9, 14); // 14 เพื่อไม่เกิน 15:00
            $randomMinute = rand(0, 59);

            $randomDateTime = $randomDay
                ->setHour($randomHour)
                ->setMinute($randomMinute)
                ->setSecond(0);

            $results[] = $randomDateTime->format('Y-m-d H:i:s');
        }

        return $results;
    }
}
