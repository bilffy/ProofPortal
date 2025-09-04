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
            $table->unsignedBigInteger('download_type_id')->after('user_id');
            $table->unsignedBigInteger('download_category_id')->after('download_type_id');
            $table->foreign('download_type_id')
                ->references('id')
                ->on('download_type');
            $table->foreign('download_category_id')
                ->references('id')
                ->on('download_category');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_requested', function (Blueprint $table) {
            $table->dropForeign(['download_type_id']);
            $table->dropForeign(['download_category_id']);
            $table->dropColumn('download_type_id');
            $table->dropColumn('download_category_id');
        });
    }
};
