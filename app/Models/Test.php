<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "quiz",
        "tester",
        "start",
        "answers",
        "score",
        "totalScore",
        "end",
        'agn',
        'course_id'
    ];

    public function getAnswersAttribute($value)
    {
        return json_decode($value, true);
    }

    /** Pass/fail by the quiz's pass_score (a removed quiz is treated as pass_score 0, as before). */
    public function isPassed(): bool
    {
        $quiz = $this->getQuiz;
        return $quiz ? $quiz->passes($this->score, $this->totalScore) : true;
    }

    public function getCourse()
    {
        return $this->belongsTo(course::class, 'course_id');
    }

    public function getTester() {
        return $this->belongsTo(User::class, 'tester');
    }

    public function getQuiz() {
        return $this->belongsTo(quiz::class, 'quiz');
    }
}
