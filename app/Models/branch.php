<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class branch extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "agency",
        "brn_id",
    ] ;

    public function agencyName() {
        return $this->belongsTo(Agency::class, 'agency');
    }


}
