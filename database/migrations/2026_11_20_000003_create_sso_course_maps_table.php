<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_course_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('code', 50);
            $table->string('name')->nullable();
            $table->unsignedBigInteger('course_group_id')->nullable();
            $table->timestamps();
            $table->unique(['source', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_course_maps');
    }
};
