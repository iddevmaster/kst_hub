<?php

namespace App\Livewire;

use Livewire\Component;

class UserRequest extends Component
{

    public $addMode = false;

    public function mount()
    {

    }

    public function switchToAddMode()
    {
        $this->addMode = true;
    }
    public function switchToReqMode()
    {
        $this->addMode = false;
    }

    public function render()
    {
        if ($this->addMode) {
            return view('livewire.add_request');
        } else {
            return view('livewire.user-request');
        }
    }
}
