<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('changelogs', function (Blueprint $table) {
                $table->dropUnique('changelogs_full_lookup_unique');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('changelogs', function (Blueprint $table) {
                $table->dropUnique(['ts_jobkey', 'keyvalue', 'keyorigin', 'issue_id']);
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('changelogs', function (Blueprint $table) {
            $table->unique(['ts_jobkey', 'keyvalue', 'keyorigin', 'issue_id'], 'changelogs_full_lookup_unique');
        });
    }
};


