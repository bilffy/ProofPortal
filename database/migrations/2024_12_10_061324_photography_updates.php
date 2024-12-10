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
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['folder_tag']);
            $table->dropColumn('folder_tag');
        });
        
        Schema::table('folders', function (Blueprint $table) {
            $table->string('folder_tag', 50)->nullable()->after('ts_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->unsignedBigInteger('folder_tag')->nullable();
            $table->foreign('folder_tag')->references('id')->on('folder_tags')->onDelete('set null');
        });
    }
};
