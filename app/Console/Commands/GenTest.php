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
    }
}
