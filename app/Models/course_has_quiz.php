<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class course_has_quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'course_id',
    ];

    public function getQuiz() {
        return $this->belongsTo(quiz::class, 'quiz_id');
    }

    public function getCourse() {
        return $this->belongsTo(course::class, 'course_id');
    }
}
