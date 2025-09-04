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
        Schema::table('jobs', function (Blueprint $table) {
            $table->dateTime('proof_catchup')->nullable()->after('proof_due');
            $table->dateTime('portrait_download_date')->nullable()->after('download_available_date');
            $table->dateTime('group_download_date')->nullable()->after('portrait_download_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['proof_catchup']);
            $table->dropColumn(['portrait_download_date']);
            $table->dropColumn(['group_download_date']);
        });
    }
};
