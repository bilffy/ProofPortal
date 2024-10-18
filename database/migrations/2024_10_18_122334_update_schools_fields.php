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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('name', 255)->change()->nullable();
            $table->string('school_logo', 255)->change()->nullable();
            $table->string('suburb', 50)->change()->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('name', 25)->change()->nullable();
            $table->string('school_logo', 25)->change()->nullable();
            $table->string('suburb', 25)->change()->nullable();
        });
    }
};
