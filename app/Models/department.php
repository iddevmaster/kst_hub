<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class department extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "personnel",
        "agency",
        "branch",
        "course",
        "prefix",
        "dpm_id",
    ] ;

    public function brnName() {
        return $this->belongsTo(branch::class, 'branch');
    }
}
