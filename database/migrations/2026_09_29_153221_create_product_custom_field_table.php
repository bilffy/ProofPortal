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
        Schema::create('product_custom_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained('custom_fields')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            // For radio / dropdown / checkbox fields: the option_values this product
            // shows (JSON array). NULL = every option of the field. Stored by value,
            // not option id, because saving a field in the admin re-creates its options.
            $table->json('allowed_options')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'custom_field_id']);
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_custom_field');
    }
};
