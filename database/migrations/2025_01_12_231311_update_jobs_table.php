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
            $table->integer('imagesync_status_id')->nullable()->after('foldersync_status_id');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['force_catchup']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['imagesync_status_id']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dateTime('force_catchup')->nullable()->after('proof_due');
        });
    }
};
