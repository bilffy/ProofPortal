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
            $table->dateTime('force_catchup')->nullable()->after('proof_due');
            $table->dateTime('download_available_date')->nullable()->after('force_catchup');
            $table->tinyInteger('notifications_enabled')->nullable()->after('force_sync');
            $table->mediumText('notifications_matrix')->nullable()->after('notifications_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['force_catchup']);
            $table->dropColumn(['download_available_date']);
            $table->dropColumn(['notifications_enabled']);
            $table->dropColumn(['notifications_matrix']);
        });
    }
};
