<?php

namespace App\Console\Commands;

use App\Models\Test;
use Illuminate\Console\Command;

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

        $sample_answer1 = json_decode($sample_test[0]->answers, true);

        $sample_answer2 = json_decode($sample_test[1]->answers, true);

        $merged = array_merge($sample_answer1, $sample_answer2);

        $random = collect($merged)
        ->shuffle()
        ->take(30);

        $count_status = $random
        ->where('status', '1')
        ->count();

        $this->info('Random sample: ' . json_encode($random));
        $this->info('Count of status 1: ' . $count_status);
        $this->info('total: ' . count($random));
    }
}
