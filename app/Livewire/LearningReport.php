<?php

namespace App\Livewire;

use App\Models\course;
use App\Models\progress;
use App\Models\User;
use App\Models\user_has_course;
use Livewire\Component;

class LearningReport extends Component
{
    public $user_courses;
    public $formData = [];
    public function mount()
    {
        $this->user_courses = user_has_course::all();
        if (auth()->user()->role == 'superAdmin') {
            $users = User::orderBy('created_at', 'desc')->get(['id']);
            $this->user_courses = user_has_course::whereIn('user_id', $users)->get();
        } else {
            $users = User::where('agency', auth()->user()->agency)->get(['id']);
            $this->user_courses = user_has_course::whereIn('user_id', $users)->get();
        }
    }

    public function render()
    {
        return view('livewire.learning-report');
    }
}
