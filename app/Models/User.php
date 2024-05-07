<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Http;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'agency',
        'brn',
        'dpm',
        'role',
        'courses',
        'icon',
        'startlt',
        'course_group',
    ];

    public function getCoursesAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'courses' => 'array',
    ];

    public function dpmName() {
        return $this->belongsTo(department::class, 'dpm');
    }
    public function getBrn() {
        return $this->belongsTo(branch::class, 'brn');
    }
    public function agnName() {
        return $this->belongsTo(agency::class, 'agency');
    }

    public function logoutFromSSOServer () {
        // Send a revoke tokens request to SSO Server
        $access_token = session()->get("access_token");
        $response = Http::withHeaders([
            "Accept" => "application/json",
            "Authorization" => "Bearer " . $access_token
        ])->get(config('auth.sso_host') . "/api/logmeout");
        // die($response);
    }

}
