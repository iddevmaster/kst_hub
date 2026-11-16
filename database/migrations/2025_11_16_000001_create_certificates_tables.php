<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('agn');
            $table->unsignedBigInteger('course_id')->nullable(); // null = default template of the agency
            $table->string('bg_image');
            $table->json('fields')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agn', 'course_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('cert_no')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->integer('score')->default(0);
            $table->integer('total_score')->default(0);
            $table->decimal('percent', 5, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('agn');
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index('agn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
