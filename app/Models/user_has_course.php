<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_has_course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
    ];

    public function getUser() {
        return user::find($this->user_id);
    }

    public function getCourse() {
        return course::find($this->course_id);
    }
}
