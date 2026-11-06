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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // $table->decimal('base_price', 12, 4)->nullable();
            $table->string('sku')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            // false: the order form has no Artwork section (only expected delivery date + other details)
            $table->boolean('show_artwork')->default(true);
            // who receives this product's Artwork/Print request email: one or more addresses, comma separated
            $table->text('print_request_email')->nullable();
            // true: the print request email also goes to the email of the submitting user's franchise
            $table->boolean('print_to_franchise')->default(false);
            // false: the requestor's copy of this product's order email is not listed in /emails/ordering/{school}
            $table->boolean('show_in_emails')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
