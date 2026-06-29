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
        Schema::create('job_users', function (Blueprint $table) {
            $table->id();
            
            // 1. Changed to unsignedInteger to match standard parent index structures
            $table->unsignedInteger('ts_job_id')->nullable(); 
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // 2. Explicitly add a local index to the column first
            $table->index('ts_job_id');

            // 3. Foreign key constraints
            $table->foreign('ts_job_id')->references('ts_job_id')->on('jobs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_users');
    }
};
