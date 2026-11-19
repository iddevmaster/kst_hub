<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoCourseMap extends Model
{
    protected $fillable = ['source', 'code', 'name', 'course_group_id'];

    public function group()
    {
        return $this->belongsTo(course_group::class, 'course_group_id');
    }
}
