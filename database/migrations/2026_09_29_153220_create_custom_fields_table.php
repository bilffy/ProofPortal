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
            // Show-only-when conditions: [{"field_id": 6, "values": ["gloss-laminated"]}, ...].
            // The field shows when ANY condition is met (a parent field has one of its values
            // selected). NULL = always show.
            $table->json('depends_on')->nullable();
            $table->string('placeholder')->nullable();
            // Text / number / phone fields: value the field starts with, and whether the
            // customer is stopped from changing it (shown greyed out, always submitted as default_value).
            $table->string('default_value')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->string('note')->nullable();
            $table->boolean('is_required')->default(true);
            // Logged-in user detail that pre-fills the field on the order page (first_name, last_name, email, phone, school_name, school_postcode, role)
            $table->string('prefill_source', 30)->nullable();
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
