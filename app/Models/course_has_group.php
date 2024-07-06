<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class course_has_group extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'course_id',
    ];

    public function getGroup() {
        return course_group::find($this->group_id);
    }

    public function getCourse() {
        return course::find($this->course_id);
    }
}
