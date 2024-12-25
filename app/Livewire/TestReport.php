<?php

namespace App\Livewire;

use App\Models\quiz;
use App\Models\Test;
use App\Models\User;
use Livewire\Component;

class TestReport extends Component
{
    public $tests;
    public $formData = [];
    public $users;
    public $quizzes;
    public function mount()
    {
        if (auth()->user()->role == 'superAdmin') {
            $this->tests = Test::orderBy('created_at', 'desc')->get();
            $this->users = User::orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->quizzes = quiz::orderBy('created_at', 'desc')->get(['id', 'title']);
        } else {
            $this->tests = Test::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->get();
            $this->users = User::where('agency', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'name']);
            $this->quizzes = quiz::where('agn', auth()->user()->agency)->orderBy('created_at', 'desc')->get(['id', 'title']);
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
        $this->tests = $filter_tests->get();
    }

    public function exportToPdf()
    {
        $this->dispatchBrowserEvent('exportToPdf'); // Trigger JavaScript function (explained later)
    }

    public function render()
    {
        return view('livewire.test-report');
    }
}