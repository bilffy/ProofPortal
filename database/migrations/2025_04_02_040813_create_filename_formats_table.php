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
        Schema::create('filename_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format');
            $table->integer('format_key')->unique();
            $table->json('visibility')->nullable();
            $table->json('visibility_options')->nullable(false);
            // 1: a portraits download using this format is blocked when a selected subject has no value for one of its fields
            $table->boolean('validation_check')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filename_formats');
    }
};
