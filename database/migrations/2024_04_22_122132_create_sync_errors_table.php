<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_errors', function (Blueprint $table) {
            $table->id();
            $table->text('error_message')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('error_path')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('error_fromtable')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('error_totable')->collation('utf8mb4_unicode_ci')->nullable();
            $table->integer('blueprintJobId')->nullable(); // Removed collation as it is an integer field
            $table->unsignedBigInteger('status_id')->nullable(); // Adjusted to unsignedBigInteger
            
            // Add foreign key constraint
            $table->foreign('status_id')
                ->references('id')
                ->on('status')
                ->onDelete('CASCADE');
            
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_errors');
    }
};
