<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'cert_no', 'user_id', 'course_id', 'quiz_id', 'test_id', 'template_id',
        'score', 'total_score', 'percent', 'issued_at', 'revoked_at', 'snapshot', 'agn',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(course::class, 'course_id');
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id')->withTrashed();
    }
}
