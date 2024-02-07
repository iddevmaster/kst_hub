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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->integer('time_limit')->default(0);
            $table->integer('pass_score')->default(50);
            $table->boolean('shuffle_quest')->default(false);
            $table->string('create_by');
            $table->boolean('showAns')->default(false);
            $table->text('for_courses')->nullable();
            $table->string('agn');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
