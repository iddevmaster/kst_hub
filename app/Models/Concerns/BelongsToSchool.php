<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * History rows (progress, tests, enrollments, certificates) belong to the school (users.brn)
 * the user was in when the row was created. Reads go through ofUserSchool() so a user who
 * moved school only sees the current school's history.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::creating(function ($model) {
            if ((string) ($model->brn ?? '') === '') {
                $uid = $model->{$model->schoolUserColumn()};
                $model->brn = (string) (User::whereKey($uid)->value('brn') ?? '');
            }
        });
    }

    /** Column holding the user id; Test overrides this with 'tester'. */
    public function schoolUserColumn(): string
    {
        return 'user_id';
    }

    public function scopeOfUserSchool(Builder $query, User $user): Builder
    {
        $table = $this->getTable();
        $query->where("{$table}.{$this->schoolUserColumn()}", (string) $user->id);

        // Only SSO users are split per school; other accounts keep their whole history
        // even when an admin moves them to another branch.
        if (!empty($user->sso_source)) {
            $query->where("{$table}.brn", (string) $user->brn);
        }
        return $query;
    }
}
