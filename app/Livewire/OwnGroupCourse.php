<?php

namespace App\Livewire;

use App\Models\course_has_group;
use Livewire\Component;
use App\Models\course_group;
use App\Models\course;

class OwnGroupCourse extends Component
{
    public $courses = [];
    public $all_courses = [];
    public $group;
    public $gName;
    public $gCourses = [];
    public $editMode = false;

    public function mount($gid)
    {
        $this->group = course_group::find($gid);
        $course_list = course_has_group::where('group_id', $this->group->id)->get(['course_id']);
        $this->courses = course::whereIn("id", $course_list ?? [])->get();
        $this->all_courses = course::where("teacher", auth()->id())->get();
        $this->gCourses = $this->courses->pluck('id')->toArray();
        $this->gName =  $this->group->name;
    }

    public function updateGroup()
    {
        try {
            $this->group->update([
                'name' => $this->gName,
            ]);
            $selectedCourses = $this->gCourses;
            foreach ($selectedCourses as $key => $selectedCourse) {
                $gcourse = course_has_group::where('group_id', $this->group->id)->where('course_id', $selectedCourse)->get();
                if (count($gcourse) == 0) {
                    course_has_group::create([
                        'group_id' => $this->group->id,
                        'course_id' => $selectedCourse,
                    ]);
                }
            }
            course_has_group::where('group_id', $this->group->id)->whereNotIn('course_id', $selectedCourses)->delete();
            $course_list = course_has_group::where('group_id', $this->group->id)->get(['course_id']);
            $this->courses = course::whereIn("id", $course_list ?? [])->get();
            session()->flash('success', 'บันทึกสำเร็จ');
        } catch (\Throwable $th) {
            session()->flash('error', 'ไม่สามารถดำเนินการได้');
        }

        $this->switchToGroupMode();
    }

    public function switchToEditMode()
    {
        $this->editMode = true;
    }
    public function switchToGroupMode()
    {
        $this->editMode = false;
    }

    public function render()
    {
        if ($this->editMode) {
            return view('livewire.edit-group-course'); // Render the edit course component
        } else {
            return view('livewire.own-group-course'); // Render the group course component
        }
    }
}
