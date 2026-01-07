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
            $table->integer('ts_job_id')->nullable(); // Matches the type in the jobs table
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Add foreign key constraints
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
