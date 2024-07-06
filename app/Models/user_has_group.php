<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_has_group extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
    ];

    public function getUser() {
        return user::find($this->user_id);
    }

    public function getGroup() {
        return course_group::find($this->group_id);
    }
}
