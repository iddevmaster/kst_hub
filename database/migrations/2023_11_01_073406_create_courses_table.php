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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->longText('description')->nullable();
            $table->string('permission');
            $table->string('teacher');
            $table->string('dpm');
            $table->json('studens')->nullable();
            $table->string('code');
            $table->text('img')->nullable();
            $table->text('group')->nullable();
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
        Schema::dropIfExists('courses');
    }
};
