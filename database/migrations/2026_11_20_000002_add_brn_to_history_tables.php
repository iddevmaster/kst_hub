<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** table => column that holds the user id */
    private array $simple = ['progress' => 'user_id', 'tests' => 'tester', 'certificates' => 'user_id'];

    /**
     * Re-runnable: every step checks what already exists, so a run that died half way
     * (MySQL DDL cannot be rolled back) can simply be started again.
     */
    public function up(): void
    {
        $this->assertNumericUserIds();

        foreach ($this->simple as $table => $userCol) {
            $index = "{$table}_{$userCol}_brn_index";
            if (!Schema::hasColumn($table, 'brn')) {
                Schema::table($table, fn (Blueprint $t) => $t->string('brn')->default(''));
            }
            if (!$this->indexExists($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->index([$userCol, 'brn'], $index));
            }
            // Portable backfill (MySQL + SQLite): the user's current branch. Only touches empty rows.
            DB::statement("UPDATE {$table} SET brn = COALESCE((SELECT users.brn FROM users WHERE users.id = {$table}.{$userCol}), '') WHERE brn = ''");
        }

        // certificates: one per (user, course, school). Add the new unique first so MySQL always has an index on user_id.
        if (!$this->indexExists('certificates', 'certificates_user_course_brn_unique')) {
            Schema::table('certificates', fn (Blueprint $t) => $t->unique(['user_id', 'course_id', 'brn'], 'certificates_user_course_brn_unique'));
        }
        if ($this->indexExists('certificates', 'certificates_user_id_course_id_unique')) {
            Schema::table('certificates', fn (Blueprint $t) => $t->dropUnique(['user_id', 'course_id']));
        }

        $this->rebuildUserHasCourses();
    }

    /** user_has_courses: rebuild with primary key (user_id, course_id, brn). */
    private function rebuildUserHasCourses(): void
    {
        if (Schema::hasTable('user_has_courses') && Schema::hasColumn('user_has_courses', 'brn')) {
            return;   // already rebuilt
        }

        // Died after the old table was dropped: the copy is complete, only the rename is missing.
        if (!Schema::hasTable('user_has_courses') && Schema::hasTable('user_has_courses_new')) {
            Schema::rename('user_has_courses_new', 'user_has_courses');
            return;
        }

        // Leftover of an earlier attempt, possibly half filled: start over.
        Schema::dropIfExists('user_has_courses_new');

        Schema::create('user_has_courses_new', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('course_id');
            $t->string('brn')->default('');
            $t->timestamps();
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $t->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $t->primary(['user_id', 'course_id', 'brn'], 'user_has_courses_user_course_brn_primary');
        });
        DB::statement("INSERT INTO user_has_courses_new (user_id, course_id, brn, created_at, updated_at)
            SELECT uhc.user_id, uhc.course_id, COALESCE((SELECT users.brn FROM users WHERE users.id = uhc.user_id), ''), uhc.created_at, uhc.updated_at
            FROM user_has_courses uhc");
        Schema::drop('user_has_courses');
        Schema::rename('user_has_courses_new', 'user_has_courses');
    }

    /**
     * progress.user_id and tests.tester are strings while users.id is a BIGINT; on MySQL a
     * non-numeric value would fail the backfill under strict mode half way through.
     * Fail before touching anything, with a message that says where to look.
     */
    private function assertNumericUserIds(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach (['progress' => 'user_id', 'tests' => 'tester'] as $table => $col) {
            $bad = DB::table($table)->whereRaw("{$col} NOT REGEXP '^[0-9]+$'")->count();
            if ($bad > 0) {
                throw new RuntimeException("{$table}.{$col} has {$bad} non-numeric value(s); clean them up before migrating (SELECT * FROM {$table} WHERE {$col} NOT REGEXP '^[0-9]+\$').");
            }
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $conn = DB::connection();
        if ($conn->getDriverName() === 'sqlite') {
            return collect($conn->select("PRAGMA index_list('{$table}')"))->contains(fn ($r) => $r->name === $name);
        }
        return count($conn->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])) > 0;
    }

    /**
     * Not a normal path: once a user holds certificates for the same course at
     * several schools, re-creating unique(user_id, course_id) will fail.
     */
    public function down(): void
    {
        // Rows that only differ by brn collapse to one per (user, course).
        Schema::create('user_has_courses_old', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('course_id');
            $t->timestamps();
            $t->primary(['user_id', 'course_id'], 'user_has_course_user_id_course_id_primary');
        });
        DB::statement('INSERT INTO user_has_courses_old (user_id, course_id, created_at, updated_at)
            SELECT user_id, course_id, MIN(created_at), MAX(updated_at) FROM user_has_courses GROUP BY user_id, course_id');
        Schema::drop('user_has_courses');
        Schema::rename('user_has_courses_old', 'user_has_courses');

        Schema::table('certificates', function (Blueprint $t) {
            $t->unique(['user_id', 'course_id']);
        });
        Schema::table('certificates', function (Blueprint $t) {
            $t->dropUnique('certificates_user_course_brn_unique');
        });
        foreach ($this->simple as $table => $userCol) {
            Schema::table($table, function (Blueprint $t) use ($table, $userCol) {
                $t->dropIndex("{$table}_{$userCol}_brn_index");
                $t->dropColumn('brn');
            });
        }
    }
};
