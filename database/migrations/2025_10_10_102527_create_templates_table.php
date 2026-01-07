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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 100)->nullable();
            $table->string('template_location', 100)->nullable();
            $table->string('template_subject', 100)->nullable();
            $table->string('template_format', 25)->nullable();
            $table->unsignedBigInteger('email_category_id')->nullable();
            $table->timestamps();
            $table->foreign('email_category_id')->references('id')->on('email_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
