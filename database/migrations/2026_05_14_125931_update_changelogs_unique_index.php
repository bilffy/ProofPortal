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
        Schema::table('changelogs', function (Blueprint $table) {
            try {
                $table->dropUnique('changelogs_ts_jobkey_keyvalue_keyorigin_unique');
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            $table->unique(['ts_jobkey', 'keyvalue', 'keyorigin', 'issue_id'], 'changelogs_full_lookup_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('changelogs', function (Blueprint $table) {
            try {
                $table->dropUnique('changelogs_full_lookup_unique');
            } catch (\Exception $e) {
                // Ignore
            }
            $table->unique(['ts_jobkey', 'keyvalue', 'keyorigin'], 'changelogs_ts_jobkey_keyvalue_keyorigin_unique');
        });
    }
};
