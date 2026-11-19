<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class progress extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        "user_id",
        "course_id",
        "lesson_id",
        "agn",
        "brn"
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course() {
        return $this->belongsTo(course::class, 'course_id');
    }

    public function lesson() {
        return $this->belongsTo(lesson::class, 'lesson_id');
    }
}
