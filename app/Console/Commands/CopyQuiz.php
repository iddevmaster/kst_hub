<?php

namespace App\Console\Commands;

use App\Models\question;
use App\Models\quiz;
use Illuminate\Console\Command;

class CopyQuiz extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:copy-quiz {quiz_id} {dest_org_id} {admin_user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy a quiz and its questions to another organization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quiz_id = $this->argument('quiz_id');
        $org_id = $this->argument('dest_org_id');
        $admin_id = $this->argument('admin_user_id');

        $this->info("find quiz : {$quiz_id}");
        $quiz = quiz::find($quiz_id);
        if ( !$quiz ) {
             $this->error("Quiz ID '{$quiz_id}' does not exist. Aborting.");
             return;
        }

        $this->info("Replicate quiz");
        $newQuiz = $quiz->replicate();
        $newQuiz->agn = $org_id;
        $newQuiz->create_by = $admin_id;
        $newQuiz->for_courses = null;
        $newQuiz->save();

        try {
            $questions = question::where('quiz', $quiz_id)->get();
            $this->info("Replicate questions: " . count($questions));
            foreach ($questions as $q) {
                $new = $q->replicate();
                $new->quiz = $newQuiz->id;
                $new->agn = $org_id;
                $new->save();
            }
            $this->info("Quiz copy completed successfully. New Quiz ID: {$newQuiz->id}");
        } catch (\Throwable $th) {
            $this->error("An error occurred while copying questions: " . $th->getMessage());
            question::where('quiz', $newQuiz->id)->delete();
        }
    }
}
