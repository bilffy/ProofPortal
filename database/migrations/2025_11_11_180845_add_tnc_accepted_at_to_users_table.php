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
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('tnc_accepted_at')->nullable()->default(null)->after('is_setup_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', callback: function (Blueprint $table) {
            $table->dropColumn('tnc_accepted_at');
        });
    }
};
