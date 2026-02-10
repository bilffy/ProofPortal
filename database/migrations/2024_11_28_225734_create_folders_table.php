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
        Schema::create('folder_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('external_name', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->timestamps();
        });
        
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->integer('ts_folder_id')->nullable();
            $table->string('ts_folderkey', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('ts_foldername', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->integer('ts_job_id')->nullable();

            $table->unsignedBigInteger('folder_tag')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('folder_tag')
                ->references('id')
                ->on('folder_tags')
                ->onDelete('set null');
            $table->foreign('status_id')
                ->references('id')
                ->on('status')
                ->onDelete('set null');
            
            $table->string('teacher', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('principal', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('deputy', 100)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            
            // Boolean Fields
            $table->tinyInteger('is_edit_portraits')->nullable();
            $table->tinyInteger('is_edit_groups')->nullable();
            $table->tinyInteger('is_edit_job_title')->nullable();
            $table->tinyInteger('is_edit_salutation')->nullable();
            $table->tinyInteger('show_prefix_suffix_portraits')->nullable();
            $table->tinyInteger('show_prefix_suffix_groups')->nullable();
            $table->tinyInteger('show_salutation_portraits')->nullable();
            $table->tinyInteger('show_salutation_groups')->nullable();
            $table->tinyInteger('is_locked')->nullable();
            $table->tinyInteger('is_visible_for_proofing')->nullable();
            $table->tinyInteger('is_visible_for_portrait')->nullable();
            $table->tinyInteger('is_visible_for_group')->nullable();
            $table->tinyInteger('is_subject_list_allowed')->nullable();
            $table->tinyInteger('is_edit_principal')->nullable();
            $table->tinyInteger('is_edit_deputy')->nullable();
            $table->tinyInteger('is_edit_teacher')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_tags');
        Schema::dropIfExists('folders');
    }
};
