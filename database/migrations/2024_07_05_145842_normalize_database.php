<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_has_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('course_id');

            $table->timestamps();

            $table->foreign('group_id')
                ->references('id')
                ->on('course_groups')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->primary(['group_id', 'course_id'], 'group_has_course_group_id_course_id_primary');
        });

        Schema::create('course_has_quizzes', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('course_id');

            $table->timestamps();

            $table->foreign('quiz_id')
                ->references('id')
                ->on('quizzes')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->primary(['quiz_id', 'course_id'], 'quiz_has_course_quiz_id_course_id_primary');
        });

        Schema::create('user_has_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->primary(['user_id', 'course_id'], 'user_has_course_user_id_course_id_primary');
        });

        Schema::create('user_has_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('group_id');

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('group_id')
                ->references('id')
                ->on('course_groups')
                ->onDelete('cascade');

            $table->primary(['user_id', 'group_id'], 'user_has_group_user_id_group_id_primary');
        });

        Schema::table('agencies', function (Blueprint $table) {
            if (!Schema::hasColumn('agencies', 'agn_id')) {
                $table->string('agn_id')->nullable()->after('id');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'brn_id')) {
                $table->string('brn_id')->nullable()->after('id');
            }
        });

        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'dpm_id')) {
                $table->string('dpm_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_has_groups');
        Schema::dropIfExists('course_has_quizzes');
        Schema::dropIfExists('user_has_courses');
        Schema::dropIfExists('user_has_groups');
    }
};
