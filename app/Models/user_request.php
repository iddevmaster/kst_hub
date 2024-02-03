<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class user_request extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "user",
        "type",
        "target",
        "content",
        "status",
        "alert",
        "finish_by"
    ];

    public function getUser() {
        return $this->belongsTo(User::class, 'user');
    }

    public function getTarget() {
        return $this->belongsTo(User::class, 'target');
    }

    public function getFinish() {
        return $this->belongsTo(User::class, 'finish_by');
    }

    public function getAlertAttribute($value)
    {
        return json_decode($value, true);
    }
}
