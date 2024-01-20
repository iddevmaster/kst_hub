<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\course;

class OwnAllCourse extends Component
{
    public $courses;

    public function mount()
    {
        $this->courses = course::where("teacher", auth()->id())->get();
    }

    public function render()
    {
        return view('livewire.own-all-course');
    }
}
