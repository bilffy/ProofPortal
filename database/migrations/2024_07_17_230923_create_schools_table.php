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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->nullable();
            $table->string('school_logo', 25)->nullable();
            $table->text('description')->nullable();
            $table->string('schoolkey', 25)->nullable();
            $table->text('address')->nullable();
            $table->integer('postcode')->nullable();
            $table->string('suburb', 25)->nullable();
            $table->string('state', 25)->nullable();
            $table->string('country', 25)->nullable();
            $table->integer('status_id')->nullable();
            $table->timestamps();
        });

        Schema::create('school_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->dateTime('photoday')->nullable();
            $table->dateTime('catchup_date')->nullable();
            $table->dateTime('digitalDownload_date')->nullable();
            $table->string('principal', 25)->nullable();
            $table->integer('ts_season_id')->nullable();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('school_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('school_franchises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('franchise_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->foreign('franchise_id')->references('id')->on('franchises')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
        Schema::dropIfExists('school_users');
        Schema::dropIfExists('school_details');
        Schema::dropIfExists('school_franchises');
    }
};
