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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Was $table->foreign('user_id')->references(...) with no user_id
            // column ever defined first - Schema::create can't add a foreign
            // key constraint onto a column that doesn't exist yet, so this
            // migration would fail outright. foreignId() defines the column
            // AND the constraint in one call, same as product_id below.
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            $table->string('order_number')->unique()->nullable(); // e.g., ORD-2026-0001
           // $table->decimal('total_amount', 10, 2)->nullable();
            $table->integer('status_id')->nullable();
            $table->foreignId('artwork_required_id')
                ->nullable()
                ->constrained('artworks_required')
                ->nullOnDelete();
            $table->date('artwork_due_date')->nullable();
            $table->longText('artwork_brief')->nullable();
            $table->text('additional_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
