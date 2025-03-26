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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description', 1024)->nullable();
            $table->string('property_group', 50);
            $table->string('property_key', 50)->unique();
            $table->string('property_value', 1024)->nullable();
            $table->json('selections')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
