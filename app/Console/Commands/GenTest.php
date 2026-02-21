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
        $this->info('----------  Start generating tests...  ----------');
        $sample_test = Test::whereIn('id', [20907, 20905])->get();
        $sample_answer1 = $sample_test[0]->answers;
        $sample_answer2 = $sample_test[1]->answers;
        $merged = $sample_answer1 + $sample_answer2;

        $randomDateTimes = $this->randomDateTimes();

        $users = User::whereBetween('username', ['CP2569041', 'CP2569070'])->whereNot('username', 'CP2569046')->get(['id', 'username']);
        foreach ($users as $index => $user) {
            $startDate = $randomDateTimes[$index % count($randomDateTimes)];
            $endDate = Carbon::parse($startDate)->addSeconds(rand(600, 1800))->format('Y-m-d H:i:s');

            // // สุ่มเฉพาะ key
            // $randomKeys = Arr::random(array_keys($merged), 30);

            // // rebuild array โดยรักษา key เดิม
            // $random = collect($randomKeys)
            //     ->mapWithKeys(function ($key) use ($merged) {
            //         return [$key => $merged[$key]];
            //     })
            //     ->toArray();

            // // นับ status = 1
            // $count_status = collect($random)
            //     ->where('status', '1')
            //     ->count();

            $result = $this->randomAnswersWithScoreControl($merged);
            $random = $result['answers'];
            $count_status = $result['score'];

            $this->info("User: {$user->username}, Score: {$count_status}, Total: {$result['total']}");

            // $new_test = new Test();
            // $new_test->quiz = $sample_test[0]->quiz;
            // $new_test->course_id = $sample_test[0]->course_id;
            // $new_test->tester = $user->id;
            // $new_test->answers = json_encode($random);
            // $new_test->score = $count_status;
            // $new_test->totalScore = count($random);
            // $new_test->agn = $sample_test[0]->agn;
            // $new_test->start = $startDate;
            // $new_test->end = $endDate;
            // $new_test->created_at = $endDate;
            // $new_test->save();
            // $this->info('Test created with ID: ' . $new_test->id . ' for user: ' . $user->username);
        }

        $this->info('----------  End generating tests  ----------');
    }

    private function randomAnswersWithScoreControl(array $merged, int $total = 30, int $minStatus1 = 25, int $maxStatus1 = 30): array
    {
        $collection = collect($merged);

        $status1 = $collection->where('status', '1');
        $status0 = $collection->where('status', '0');

        // สุ่มจำนวน status=1 ที่ต้องการ
        $desiredStatus1 = random_int($minStatus1, $maxStatus1);

        // ป้องกันกรณี status1 มีไม่พอ
        $desiredStatus1 = min($desiredStatus1, $status1->count());

        $desiredStatus0 = $total - $desiredStatus1;

        // สุ่มโดยรักษา key
        $randomStatus1 = $status1->count() > 0
            ? $status1->random($desiredStatus1)
            : collect();

        $randomStatus0 = $status0->count() > 0
            ? $status0->random(min($desiredStatus0, $status0->count()))
            : collect();

        $random = $randomStatus1
            ->concat($randomStatus0)
            ->shuffle();

        return [
            'answers' => $random->toArray(),
            'score' => $random->where('status', '1')->count(),
            'total' => $random->count(),
        ];
    }

    public function randomDateTimes()
    {
        // $startDate = Carbon::create(2026, 2, 17, 9, 0, 0);
        // $endDate   = Carbon::create(2026, 2, 19, 15, 0, 0);

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
