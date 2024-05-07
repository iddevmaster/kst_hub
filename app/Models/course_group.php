<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class course_group extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "courses",
        "by",
        'agn',
        'code'
    ] ;

    public function getCoursesAttribute($value)
    {
        return json_decode($value, true);
    }
}
