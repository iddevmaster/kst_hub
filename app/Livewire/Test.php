<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\question;
use Carbon\Carbon;
use App\Models\quiz;

class Test extends Component
{

    public $testId;
    public $courseId;
    public $quiz;
    public $currentQuestion = 1;
    public $totalQuestion = 0;
    public $questions;
    public $startTest;
    public $endTest;
    public $answers = []; // สำหรับเก็บคำตอบ  [key] = question id
    public $quesType = [];
    public $submitAns = [];



    public function mount($testId, $courseId, $ques_num)
    {
        $this->testId = $testId;
        $this->courseId = $courseId;
        $this->startTest = Carbon::now()->format('Y-m-d H:i:s');
        $this->quiz = quiz::find($this->testId);

        // แบบทดสอบถูกลบไปแล้ว แต่บทเรียนยังอ้างถึงอยู่
        if (!$this->quiz) {
            session()->flash('error', __('messages.quiz_not_found'));
            return redirect()->route('course.detail', ['id' => $this->courseId]);
        }

        $all_ques = Question::where('quiz', $this->testId)->count();

        if ($this->quiz->shuffle_quest && $ques_num >= $all_ques) {
            $this->questions = Question::where('quiz', $this->testId)->get()->shuffle();  // เรียงจากน้อยไปมาก
        } elseif (!$this->quiz->shuffle_quest && $ques_num >= $all_ques) {
            $this->questions = Question::where('quiz', $this->testId)->orderBy('id', 'asc')->get();  // เรียงจากน้อยไปมาก
        } else {
            $this->questions = Question::where('quiz', $this->testId)->inRandomOrder()->limit($ques_num)->get();
        }
        $this->totalQuestion = count($this->questions ?? []);

        // ไม่มีคำถามให้ทำ (คำถามถูกลบทั้งหมด หรือ ques_num ใน url ไม่ถูกต้อง) -> render() จะพังถ้าปล่อยผ่าน
        if ($this->totalQuestion < 1) {
            session()->flash('error', __('messages.quiz_no_question'));
            return redirect()->route('course.detail', ['id' => $this->courseId]);
        }

        session()->put('shuffled_questions', $this->questions);
        // dd($this->questions);
        $this->initAnswers();
    }

    private function initAnswers()
    {
        foreach ($this->questions ?? [] as $question) {
            $this->answers[$question->id] = null;
            $this->submitAns[$question->id] = null;
            $this->quesType[$question->id] = $question->type ? 'c': 't';
        }
    }

    public function gotoNextQuestion()
    {
        // $this->questions = session()->get('shuffled_questions');
        if ($this->currentQuestion < $this->totalQuestion) {
            $this->currentQuestion++;
        } else {
            $this->submitTest();
        }
    }

    public function gotoPreviousQuestion()
    {
        // $this->questions = session()->get('shuffled_questions');
        if ($this->currentQuestion > 1) {
            $this->currentQuestion--;
        }
    }

    public function submitTest()
    {
        $this->endTest = Carbon::now()->format('Y-m-d H:i:s');
        // $this->questions = session()->get('shuffled_questions');
        foreach ($this->answers as $key => $ans) {
            if ($this->quesType[$key] == 'c') {
                $this->submitAns[$key] = [
                    "ans"=> $ans[0] ?? 0,
                    "status"=> $ans[1] ?? 0,
                    "type"=> $this->quesType[$key] ?? 'c',
                ];
            } else {
                $ques = question::find($key);
                $qAns = $ques->answer[0]['answer'];

                // Remove all spaces
                $qAns = str_replace(' ', '', $qAns);
                $ans = str_replace(' ', '', $ans);

                // Convert to lowercase
                $qAns = strtolower($qAns);
                $ans = strtolower($ans);

                $this->submitAns[$key] = [
                    "ans"=> $ans,
                    "status"=> ($ans == $qAns ? '1' : '0' ),
                    "type"=> $this->quesType[$key],
                ];
            }
        }

        // dd('submit: '. $this->endTest);
        // บันทึกคำตอบลงฐานข้อมูล
        // foreach ($this->answers as $questionId => $optionId) {
        //     $submission = new TestSubmission();
        //     $submission->question_id = $questionId;
        //     $submission->option_id = $optionId;
        //     $submission->save();
        // }
        // ลิงก์ไปยังหน้าผลลัพธ์หรือการแจ้งเตือน
        session()->flash('testResults', [
            'quiz'=> $this->testId,
            'courseId'=> $this->courseId,
            'questions'=> $this->questions,
            'start' => $this->startTest,
            'end' => $this->endTest,
            'submitAns' => $this->submitAns,
        ]);
        return redirect()->route('test.finish');
    }


    public function render()
    {
        return view('livewire.test', [
            'question' => $this->questions ? $this->questions[$this->currentQuestion - 1] : [],
            'questNum' => $this->currentQuestion,
            'total'=> $this->totalQuestion,
        ]);
    }
}
