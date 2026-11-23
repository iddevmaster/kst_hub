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

    /**
     * Rules for a group code typed by an admin. The code is what SSO students are matched on
     * (`course_groups.code`), so it must be unique across all groups and use plain characters.
     */
    public static function codeRules(?int $ignoreId = null): array
    {
        return [
            'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
            \Illuminate\Validation\Rule::unique('course_groups', 'code')->ignore($ignoreId),
        ];
    }

    public static function codeMessages(): array
    {
        return [
            'gcode.regex' => __('messages.group_code_format'),
            'gcode.unique' => __('messages.group_code_taken'),
            'gcode.max' => __('messages.group_code_format'),
        ];
    }

    /** Groups synced from the legacy SSO are owned by that sync: it rewrites or deletes them by code. */
    public function isSyncedFromSso(): bool
    {
        return $this->by === 'api:sso';
    }

    public function getCoursesAttribute($value)
    {
        return json_decode($value, true);
    }
}
