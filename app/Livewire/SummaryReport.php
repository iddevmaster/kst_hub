<?php

namespace App\Livewire;

use Date;
use Livewire\Component;
use Livewire\WithPagination;

use App\Models\branch;
use App\Models\quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SummaryReport extends Component
{
    use WithPagination;

    protected $testsQuery;
    public $formData = [
        'filter_user' => null,
        'filter_brn' => null,
        'filter_quiz' => null,
        'filter_sdate' => null,
        'filter_edate' => null,
    ];
    public $users;
    public $quizzes;
    public $branches;
    public function mount()
    {
        // $this->formData['filter_sdate'] = now()->format('Y-m-d');
        if (auth()->user()->role == 'superAdmin') {
            $this->users = User::orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->branches = branch::orderBy('created_at', 'desc')->get();
            $this->quizzes = quiz::orderBy('created_at', 'desc')->get(['id', 'title']);
            $this->testsQuery = $this->setupQuery(true);
        } else {
            // $this->tests = Test::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->paginate(10);
            $this->users = User::where('agency', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->quizzes = quiz::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'title']);
            $this->branches = branch::where('agency', auth()->user()->agency)->orderBy('created_at', 'desc')->get();
            $this->testsQuery = $this->setupQuery();
        }
    }

    public function setupQuery($isAdmin = false)
    {
        $query = Test::select(
            'tester',
            'quiz',
            'course_id',
            DB::raw('COUNT(*) as times_tested'),
            DB::raw('MAX(score) as best_score'),
            DB::raw('ROUND(AVG(score), 2) as average_score'),
            DB::raw('MAX(created_at) as latest_at')
        )->groupBy(['quiz', 'course_id', 'tester']);
        if (!$isAdmin) {
            $query->where('agn', auth()->user()->agency);
        }
        if (array_key_exists('filter_sdate', $this->formData) && $this->formData['filter_sdate'] != null) {
            $query->where('created_at', '>=', $this->formData['filter_sdate'] . ' 00:00:00')
            ->where('created_at', '<=', $this->formData['filter_sdate'] . ' 23:59:59');
        }
        return $query->orderBy(DB::raw('ROUND(AVG(score), 2)'), 'desc');
    }

    public function searchTest()
    {

        // Start with the base query for tests
        $searchQuery = Test::select(
            'tester',
            'quiz',
            'course_id',
            DB::raw('COUNT(*) as times_tested'),
            DB::raw('MAX(score) as best_score'),
            DB::raw('ROUND(AVG(score), 2) as average_score'),
            DB::raw('MAX(created_at) as latest_at')
        )->groupBy(['quiz', 'course_id', 'tester']);

        // Check if formData has key filter_user
        if (array_key_exists('filter_user', $this->formData) && $this->formData['filter_user'] != null) {
            $searchQuery->where('tester', $this->formData['filter_user']);
        }

        // Check if formData has key filter_brn
        if (array_key_exists('filter_brn', $this->formData) && $this->formData['filter_brn'] != null) {
            $user_list = User::where('brn', $this->formData['filter_brn'])->pluck('id')->toArray() ?? [];
            $searchQuery->whereIn('tester', $user_list);
        }

        // Check if formData has key filter_quiz
        if (array_key_exists('filter_quiz', $this->formData) && $this->formData['filter_quiz'] != null) {
            $searchQuery->where('quiz', $this->formData['filter_quiz']);
        }

        // Check if formData has key filter_sdate
        if (array_key_exists('filter_sdate', $this->formData) && $this->formData['filter_sdate'] != null) {
            $searchQuery->where('created_at', '>=', $this->formData['filter_sdate'] . ' 00:00:00')
            ->where('created_at', '<=', $this->formData['filter_sdate'] . ' 23:59:59');
        }

        // Check if formData has key filter_edate
        // if (array_key_exists('filter_edate', $this->formData) && $this->formData['filter_edate'] != null) {
        //     $filter_tests = $filter_tests->where('created_at', '<=', $this->formData['filter_edate']);
        // }

        // Update testsQuery with the filtered query
        $this->testsQuery = $searchQuery->orderBy(DB::raw('ROUND(AVG(score), 2)'), 'desc');
    }

    public function render()
    {
        $tests = $this->testsQuery->paginate(20);

        return view('livewire.summary-report', [
            'tests' => $tests,
        ]);
    }
}
