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
        // Add index ts_folder_id to folders
        if (!Schema::hasIndex('folders', 'folders_ts_folder_id_index')) {
            Schema::table('folders', function (Blueprint $table) {
                $table->index('ts_folder_id'); 
            });
        }
        // Add index ts_subject_id to subjects
        if (!Schema::hasIndex('subjects', 'subjects_ts_subject_id_index')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->index('ts_subject_id');
            });
        }

        Schema::create('folder_subjects', function (Blueprint $table) {
            $table->id();
            $table->integer('ts_folder_id')->nullable();  
            $table->integer('ts_subject_id')->nullable();
            $table->timestamps();
            $table->foreign('ts_folder_id')->references('ts_folder_id')->on('folders')->onDelete('cascade');
            $table->foreign('ts_subject_id')->references('ts_subject_id')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_subjects');
        // Drop the indexes
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex('subjects_ts_subject_id_index');
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex('folders_ts_folder_id_index');
        });
    }
};
