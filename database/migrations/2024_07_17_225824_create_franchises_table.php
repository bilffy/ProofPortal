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
        Schema::create('franchises', function (Blueprint $table) {
            $table->id();
            $table->integer('ts_account_id')->nullable();
            $table->string('alphacode', 25)->nullable();
            $table->string('name', 25)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('postcode', 25)->nullable();
            $table->string('suburb', 25)->nullable();
            $table->string('state', 25)->nullable();
            $table->string('country', 25)->nullable();
            $table->integer('status_id')->nullable();
            $table->timestamps();
        });

        Schema::create('franchise_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('franchise_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('franchise_id')->references('id')->on('franchises')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchises');
        Schema::dropIfExists('franchise_users');
    }
};
