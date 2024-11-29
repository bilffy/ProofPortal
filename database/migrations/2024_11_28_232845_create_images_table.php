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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->integer('ts_image_id')->nullable();
            $table->string('ts_imagekey', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->integer('ts_job_id')->nullable();
            $table->string('keyvalue', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('keyorigin', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->integer('image_type_id')->nullable();
            $table->tinyInteger('protected')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
