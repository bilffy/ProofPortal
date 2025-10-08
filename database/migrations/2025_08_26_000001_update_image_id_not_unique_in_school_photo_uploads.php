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
        Schema::table('school_photo_uploads', function (Blueprint $table) {
            // drop foreign key and unique constraint before adding foreign key constraint again
            $table->dropForeign('school_photo_uploads_image_id_foreign'); 
            $table->dropUnique('school_photo_uploads_image_id_unique');
            $table->foreign('image_id')->references('id')->on('images')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_photo_uploads', function (Blueprint $table) {
            $table->unique('image_id', 'school_photo_uploads_image_id_unique');
        });
    }
};
