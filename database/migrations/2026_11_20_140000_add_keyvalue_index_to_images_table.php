<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// images.keyvalue is looked up on every download / proofing image request; without an index each lookup scans the table.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('images', ['keyvalue'])) {
            Schema::table('images', function (Blueprint $table) {
                $table->index('keyvalue', 'images_keyvalue_index');
            });
        }
        if (!Schema::hasIndex('images', ['ts_job_id'])) {
            Schema::table('images', function (Blueprint $table) {
                $table->index('ts_job_id', 'images_ts_job_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('images', 'images_keyvalue_index')) {
            Schema::table('images', fn (Blueprint $table) => $table->dropIndex('images_keyvalue_index'));
        }
        if (Schema::hasIndex('images', 'images_ts_job_id_index')) {
            Schema::table('images', fn (Blueprint $table) => $table->dropIndex('images_ts_job_id_index'));
        }
    }
};
