<?php

namespace App\Console\Commands;

use App\Models\question;
use Illuminate\Console\Command;

class InitQuestionOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize order field for questions by quiz';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ดึง quiz ID ทั้งหมดที่มีคำถามที่ order ยังเป็น null
        $quizIds = Question::whereNull('order')->select('quiz')->distinct()->pluck('quiz');

        foreach ($quizIds as $quizId) {
            // ดึงคำถามเฉพาะที่ order ยังเป็น null ใน quiz นี้
            $questionsToUpdate = Question::where('quiz', $quizId)
                ->whereNull('order')
                ->orderBy('id') // เรียงตาม id เดิมก่อน
                ->get();

            // หาค่า max order ปัจจุบันใน quiz นี้
            $maxOrder = Question::where('quiz', $quizId)->whereNotNull('order')->max('order');
            $startOrder = is_null($maxOrder) ? 0 : $maxOrder + 1;

            $order_num = 1;
            foreach ($questionsToUpdate as $index => $question) {
                $question->order = $startOrder + $order_num;
                $question->save();
                $order_num++;
            }

            $this->info("✔️ Updated order for quiz ID: {$quizId}");
        }

        $this->info("✅ Initialization complete!");
    }
}
