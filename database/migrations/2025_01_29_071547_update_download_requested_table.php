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
            $table->unsignedBigInteger('status_id')->nullable()->after('filters');
            $table->dateTime('email_sent_date')->nullable()->after('status_id');
            $table->foreign('status_id')
                ->references('id')
                ->on('status');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_requested', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
            $table->dropColumn('email_sent_date');
        });
    }
};
