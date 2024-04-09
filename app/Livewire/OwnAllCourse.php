<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\course;
use Auth;

class OwnAllCourse extends Component
{
    public $courses;

    public function mount()
    {
        if (Auth::user()->hasRole('admin')) {
            $this->courses = course::orderBy('id', 'desc')->where('agn', Auth::user()->agency)->get();
        } elseif  (Auth::user()->hasRole('superadmin')) {
            $this->courses = course::orderBy('id', 'desc')->get();
        }
        else {
            $this->courses = course::where("teacher", Auth::user()->id)->where('agn', Auth::user()->agency)->get();
        }
    }

    public function render()
    {
        return view('livewire.own-all-course');
    }
}
