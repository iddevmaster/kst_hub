<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\question;
use App\Models\quiz;
use App\Models\Test;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Activitylog;

class QuizController extends Controller
{
    public function index(Request $request) {
        if (auth()->user()->hasRole('superAdmin')) {
            $quizs = quiz::orderBy('id', 'desc')->get();
        } elseif (auth()->user()->hasRole('admin')) {
            $quizs = quiz::where('agn', auth()->user()->agency)->orderBy('id', 'desc')->get();
        } else {
            $quizs = quiz::where('create_by', auth()->user()->id)->orderBy('id', 'desc')->get();
        }

        Log::channel('activity')->info('User '. $request->user()->name .' visited allquizzes',
        [
            'user' => $request->user(),
        ]);
        if ($request->user()->hasPermissionTo('quiz')) {
            return view("page.quizzes.allquizzes", compact("quizs"));
        } else {
            return redirect('/');
        }
    }

    public function testRecord(Request $request, $qid) {
        $quiz = quiz::find($qid);
        $testes = Test::where('quiz', $qid)->orderBy('id', 'desc')->get();

        Log::channel('activity')->info('User '. $request->user()->name .' visited test record',
        [
            'user' => $request->user(),
            'quiz' => $quiz
        ]);
        return view("page.quizzes.test_record", compact("qid", "testes", "quiz"));
    }

    public function addQuestion(Request $request, $id) {
        $quiz = quiz::find($id);

        Log::channel('activity')->info('User '. $request->user()->name .' visited add question',
        [
            'user' => $request->user(),
            'quiz' => $quiz,
        ]);
        return view("page.quizzes.add_question", compact("id", "quiz"));
    }

    public function editQuestion(Request $request, $qid, $id) {
        $quiz = quiz::find($qid);
        $quest = Question::find($id);

        Log::channel('activity')->info('User '. $request->user()->name .' visited edit question',
        [
            'user' => $request->user(),
            'question' => $quest,
        ]);
        return view("page.quizzes.quest_edit", compact("id","quest","quiz"));
    }

    public function quizDetail(Request $request, $id) {
        $questions = question::where("quiz", $id)->get();
        $quiz = quiz::find($id);

        Log::channel('activity')->info('User '. $request->user()->name .' visited quiz detail',
        [
            'user' => $request->user(),
            'quiz' => $quiz,
        ]);
        return view("page.quizzes.quiz_detail", compact("id", "questions", 'quiz'));
    }

    public function store(Request $request) {
        $request->validate([
            'quizname' => ['required', 'string', 'max:1000'],
            'passScore' => ['required', 'max:10'],
        ]);
        try {
            $quiz = quiz::create([
                'title'=> $request->quizname,
                'time_limit'=> 0,
                'pass_score'=> $request->passScore,
                'shuffle_quest'=> $request->shuffq ?? false,
                'create_by'=> $request->user()->id,
                'showAns' => $request->showAns ?? false,
                'agn' => $request->user()->agency
            ]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Quiz',
                'content' => $quiz->id,
                'note' => 'store',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' store quiz',
            [
                'user' => $request->user(),
                'quiz' => $quiz,
            ]);
            return redirect()->back()->with('success','Quiz has been saved.');
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->with('error',$th->getMessage());
        }
    }

    public function update(Request $request, $id) {
        $request->validate([
            'quizname' => ['required', 'string', 'max:1000'],
            'passScore' => ['required', 'max:10'],
        ]);
        try {
            $quiz = quiz::find($id);
            $quiz->update([
                'title'=> $request->quizname,
                'time_limit'=> 0,
                'pass_score'=> $request->passScore,
                'shuffle_quest'=> $request->shuffq ?? false,
                'showAns' => $request->showAns ?? false,
            ]);

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Quiz',
                'content' => $quiz->id,
                'note' => 'update',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update quiz',
            [
                'user' => $request->user(),
                'quiz' => $quiz,
            ]);
            return redirect()->back()->with('success','Quiz has been updated.');
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->with('error',$th->getMessage());
        }
    }

    public function destroy(Request $request, $id) {
        try {
            $quiz = quiz::find($id);

            if ($quiz) {
                $quiz->delete();
            }

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Quiz',
                'content' => $quiz->id,
                'note' => 'delete',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' delete quiz',
            [
                'user' => $request->user(),
                'quiz' => $quiz,
            ]);
            return response()->json(['success' => 'Question has been deleted.']);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function storeQuestion(Request $request, $id) {
        try {
            $question = new question;
            $question->quiz = $id;
            $question->title = $request->title;
            $question->score = $request->score;
            $question->shuffle_ch = $request->shuffle ?? false;

            $choices = [];

            if ($request->ansType) // type 1 = choice , type 0 = text
            {
                $choicesNum = $request->choices ?? 0;
                for ($i=1; $i <= $choicesNum; $i++) {
                    $choices[] = [
                        'id'=> $i,
                        'type'=> 'choice',
                        'text'=> $request->input('choice'.$i),
                        'answer'=> $request->input('answer'.$i, 0),
                    ];
                }
            } else {
                $choices[] = [
                    'id'=> 't',
                    'type'=> 'text',
                    'text'=> '',
                    'answer'=> $request->writing,
                ];
            }

            $question->answer = json_encode( $choices );
            $question->type = $request->ansType;  // type 1 = choice , type 0 = text
            $question->agn = auth()->user()->agency;

            $store_audio = [];
            if ($request->audios) {
                foreach ($request->audios as $audio) {
                    if ($audio) {
                        $store_audio[] = $audio;
                    }
                }
            }

            if (count($store_audio) > 0) {
                $question->audio = json_encode($store_audio);
            }

            $question->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Question',
                'content' => $question->id,
                'note' => 'store',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' store question',
            [
                'user' => $request->user(),
                'question' => $question,
            ]);
            return redirect()->route('quiz.detail', ['id' => $id])->with('success','Question has been saved.');
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->with('error',$th->getMessage());
        }
    }

    public function updateQuestion(Request $request, $id) {
        try {
            $question = Question::find($id);
            $question->title = $request->title;
            $question->score = $request->score;
            $question->shuffle_ch = $request->shuffle ?? false;

            $choices = [];

            if ($request->ansType) // type 1 = choice , type 0 = text
            {
                $choicesNum = $request->choices ?? 0;
                for ($i=1; $i <= $choicesNum; $i++) {
                    $choices[] = [
                        'id'=> $i,
                        'type'=> 'choice',
                        'text'=> $request->input('choice'.$i),
                        'answer'=> $request->input('answer'.$i, 0),
                    ];
                }
            } else {
                $choices[] = [
                    'id'=> 't',
                    'type'=> 'text',
                    'text'=> '',
                    'answer'=> $request->writing,
                ];
            }

            $store_audio = [];
            if ($request->audios) {
                foreach ($request->audios as $audio) {
                    if ($audio) {
                        $store_audio[] = $audio;
                    }
                }
            }
            if (count($store_audio) > 0) {
                $question->audio = json_encode($store_audio);
            }

            $question->answer = json_encode( $choices );
            $question->type = $request->ansType;  // type 1 = choice , type 0 = text
            $question->save();

            Activitylog::create([
                'user' => auth()->id(),
                'module' => 'Question',
                'content' => $question->id,
                'note' => 'update',
                'agn' => auth()->user()->agency
            ]);
            Log::channel('activity')->info('User '. $request->user()->name .' update question',
            [
                'user' => $request->user(),
                'question' => $question,
            ]);
            return redirect()->route('quiz.detail', ['id' => $question->quiz])->with('success','Question has been updated.');

        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->with('error',$th->getMessage());
        }
    }

    public function delQuestion(Request $request, $id) {
        try {
            $question = question::find($id)->delete();
            return response()->json(['success' => 'Question has been deleted.']);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function copyQuiz(Request $request, $id) {
        $quiz = quiz::find($id);
        $questions = question::where('quiz', $id)->get();
        if ($quiz) {
            $copyQuiz = $quiz->replicate([
                'for_courses',
                'deleted_at'
            ])->fill([
                'title' => $quiz->title . '-copy'
            ]);
            $copyQuiz->save();

            if ($questions) {
                foreach ($questions as $index => $question) {
                    $copyQues = $question->replicate()->fill([
                        'quiz' => $copyQuiz->id
                    ]);
                    $copyQues->save();
                }
            }
        }

        return response()->json(['success' => $copyQuiz]);
    }

    public function importQues(Request $request, $qid) {
        $datas = $request->all();
        array_shift($datas);
        foreach ($datas as $data) {
            $quiz = quiz::find($data[0]);
            $quest_num = question::where('quiz', $quiz->id)->count();
            if ($data[1] > $quest_num) {
                return redirect()->back()->with('error','The number of questions is greater than the number of questions in the quiz.');
            }
            if ($qid !== $data[0]) {
                if ($data[2] ?? false) {
                    $questions = Question::where('quiz', $data[0])->inRandomOrder()->limit($data[1])->get();
                } else {
                    $questions = Question::where('quiz', $data[0])->limit($data[1])->get();
                }

                // duplicate questions
                if ($questions) {
                    foreach ($questions as $question) {
                        $copyQues = $question->replicate()->fill([
                            'quiz' => $qid
                        ]);
                        $copyQues->save();
                    }
                }
            }
        }
        return redirect()->back()->with('success','Questions has been imported.');
    }
}
