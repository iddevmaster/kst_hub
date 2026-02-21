<?php

namespace App\Console\Commands;

use App\Models\Test;
use App\Models\User;
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

        $users = User::whereBetween('username', ['CP2569041', 'CP2569070'])->get();
        $this->info('Users: ' . $users->pluck('username')->join(', '));
    }
}
