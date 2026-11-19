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
        'id_number',
        'sso_source',
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

    /** superAdmin, or admin of the subscription SSO's shared agency. */
    public function canManageSubscriptionSso(): bool
    {
        if ($this->hasRole('superAdmin')) {
            return true;
        }
        if (!$this->hasRole('admin')) {
            return false;
        }
        $agencyId = agency::where('agn_id', config('services.sso_subscription.agency'))->value('id');
        return $agencyId !== null && (string) $this->agency === (string) $agencyId;
    }

    public function logoutFromSSOServer () {
        // Revoke tokens on the SSO this session came from; failures are silent, the session is cleared anyway.
        try {
            $sso = session()->get('sso', []);
            if (($sso['source'] ?? null) === 'subscription') {
                app(\App\Services\Sso\SubscriptionSsoClient::class)->logout((string) ($sso['access_token'] ?? ''));
                return;
            }
            $access_token = session()->get("access_token");
            $response = Http::withHeaders([
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $access_token
            ])->get(config('auth.sso_host') . "/api/logmeout");
        } catch (\Throwable $th) {
            // throw $th;
        }
        // die($response);
    }

}
