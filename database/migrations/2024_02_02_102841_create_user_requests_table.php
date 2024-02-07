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
        Schema::create('user_requests', function (Blueprint $table) {
            $table->id();
            $table->string('user');
            $table->string('type');
            $table->string('target');
            $table->text('content');
            $table->string('status')->default('0');
            $table->string('alert')->nullable();
            $table->string('finish_by')->nullable();
            $table->string('agn');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_requests');
    }
};
