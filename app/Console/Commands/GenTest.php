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

        $sample_answer1 = $sample_test[0]->answers;
        $this->info('Sample answer 1: ' . json_encode($sample_answer1));

        $sample_answer2 = $sample_test[1]->answers;
        $this->info('Sample answer 2: ' . json_encode($sample_answer2));
    }
}
