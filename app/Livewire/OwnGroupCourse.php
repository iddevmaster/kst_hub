<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\course_group;
use App\Models\course;

class OwnGroupCourse extends Component
{
    public $courses = [];
    public $group;

    public function mount($gid)
    {
        $this->group = course_group::find($gid);
        $this->courses = course::whereIn("id", $this->group->courses ?? [])->get();
    }
    
    public function render()
    {
        return view('livewire.own-group-course');
    }
}
