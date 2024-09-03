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
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->integer('ts_season_id')->nullable();
            $table->integer('ts_account_id')->nullable();
            $table->integer('ts_job_id')->nullable();
            $table->string('ts_jobkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('ts_jobname', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('ts_schoolkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->unsignedBigInteger('jobsync_status_id')->nullable();
            $table->unsignedBigInteger('foldersync_status_id')->nullable();
            $table->unsignedBigInteger('job_status_id')->nullable();
            $table->dateTime('proof_start')->nullable();
            $table->dateTime('proof_warning')->nullable();
            $table->dateTime('proof_due')->nullable();
            $table->integer('force_sync')->nullable();
            $table->timestamps();
            $table->foreign('jobsync_status_id')->references('id')->on('status')->onDelete('cascade');
            $table->foreign('foldersync_status_id')->references('id')->on('status')->onDelete('cascade');
            $table->foreign('job_status_id')->references('id')->on('status')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
