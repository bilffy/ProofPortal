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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('type')->nullable(); // radio, dropdown, input, number, image, checkbox
            $table->string('image')->nullable();
            $table->foreignId('depends_on_field_id')
                  ->nullable()
                  ->constrained('custom_fields')
                  ->nullOnDelete();
            $table->json('depends_on_values')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
