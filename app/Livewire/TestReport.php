<?php

namespace App\Livewire;

use App\Models\branch;
use App\Models\quiz;
use App\Models\Test;
use App\Models\User;

use Livewire\Component;
use Livewire\WithPagination;

class TestReport extends Component
{
    use WithPagination;

    protected $testsQuery;
    public $formData = [];
    public $users;
    public $quizzes;
    public $branches;
    public function mount()
    {
        if (auth()->user()->role == 'superAdmin') {
            $this->testsQuery = Test::orderBy('created_at', 'desc');
            $this->users = User::orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->branches = branch::orderBy('created_at', 'desc')->get();
            $this->quizzes = quiz::orderBy('created_at', 'desc')->get(['id', 'title']);
        } else {
            $this->testsQuery = Test::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc');
            $this->users = User::where('agency', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->quizzes = quiz::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'title']);
            $this->branches = branch::where('agency', auth()->user()->agency)->orderBy('created_at', 'desc')->get();
        }
    }

    public function searchTest()
    {

        $filter_tests = Test::orderBy('created_at', 'desc');
        // check if formdata has key filter_user
        if (array_key_exists('filter_user', $this->formData)) {
            if ($this->formData['filter_user'] != null) {
                $filter_tests = $filter_tests->where('tester', $this->formData['filter_user']);
            }
        }
        // check if formdata has key filter_course
        if (array_key_exists('filter_brn', $this->formData)) {
            if ($this->formData['filter_brn'] != null) {
                $user_list = User::where('brn', $this->formData['filter_brn'])->pluck('id')->toArray() ?? [];
                $filter_tests = $filter_tests->whereIn('tester', $user_list);
            }
        }
        if (array_key_exists('filter_quiz', $this->formData)) {
            if ($this->formData['filter_quiz'] != null) {
                $filter_tests = $filter_tests->where('quiz', $this->formData['filter_quiz']);
            }
        }
        if (array_key_exists('filter_sdate', $this->formData)) {
            if ($this->formData['filter_sdate'] != null) {
                $filter_tests = $filter_tests->where('created_at', '>=', $this->formData['filter_sdate']);
            }
        }
        if (array_key_exists('filter_edate', $this->formData)) {
            if ($this->formData['filter_edate'] != null) {
                $filter_tests = $filter_tests->where('created_at', '<=', $this->formData['filter_edate']);
            }
        }
        $this->testsQuery = $filter_tests;
    }

    public function render()
    {
        $tests = $this->testsQuery->paginate(20);
        return view('livewire.test-report', [
            'tests' => $tests,
        ]);
    }
}
