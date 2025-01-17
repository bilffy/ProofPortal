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
        Schema::create('download_requested', function (Blueprint $table) {
            $table->id();
            $table->string('requested_by', 255)->nullable();
            $table->dateTime('requested_date');
            $table->dateTime('completed_date');
            $table->timestamps();
        });

        Schema::create('download_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('download_id')->nullable();
            $table->string('ts_jobkey', 255)->nullable();
            $table->string('keyorigin', 255)->nullable();
            $table->string('keyvalue', 255)->nullable();
            $table->foreign('download_id')->references('id')->on('download_requested')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_details');
        Schema::dropIfExists('download_requested');
    }
};
