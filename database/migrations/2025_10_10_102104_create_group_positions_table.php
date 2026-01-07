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
        Schema::create('group_positions', function (Blueprint $table) {
            $table->id();
            $table->string('ts_jobkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();  
            $table->string('ts_folderkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('ts_subjectkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('subject_full_name', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->text('row_description')->nullable();
            $table->integer('row_number')->nullable();
            $table->integer('row_position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_positions');
    }
};
