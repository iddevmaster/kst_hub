<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "course",
        "topic",
        "desc",
        "sub_lessons",
        "agn"
    ] ;

    public function getCourse() {
        return $this->belongsTo(course::class, 'course');
    }

}
