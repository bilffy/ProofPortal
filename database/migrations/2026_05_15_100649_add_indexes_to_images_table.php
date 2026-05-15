<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // This creates a composite index for your scheduler query
            $table->index(['ts_job_id', 'exportStatus', 'keyorigin'], 'idx_images_sync_lookup');
            
            // If ts_jobkey is frequently used to find specific images, add it too
            $table->index('ts_imagekey'); 
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('idx_images_sync_lookup');
            $table->dropIndex(['ts_imagekey']);
        });
    }
};