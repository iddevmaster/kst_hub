<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class question extends Model
{
    use HasFactory, SoftDeletes ;

    protected $fillable = [
        "quiz",
        "title",
        "score",
        "shuffle_ch",
        "answer",
        'agn',
        'lang'
    ];

    public function getAnswerAttribute($value)
    {
        return json_decode($value, true);
    }
}
