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
        Schema::create('activitylogs', function (Blueprint $table) {
            $table->id();
            $table->string('user');
            $table->string('module');
            $table->longText('content');
            $table->text('note')->nullable();
            $table->string('agn');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activitylogs');
    }
};
