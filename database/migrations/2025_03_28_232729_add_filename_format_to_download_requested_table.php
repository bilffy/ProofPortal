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
        Schema::table('download_requested', function (Blueprint $table) {
            $table->integer('filename_format')->default(1)->after('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_requested', function (Blueprint $table) {
            $table->dropColumn('filename_format');
        });
    }
};
