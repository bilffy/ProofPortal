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
        Schema::table('folders', function (Blueprint $table) {
            $table->string('ts_foldername', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->change();
            $table->string('teacher', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->change();
            $table->string('principal', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->change();
            $table->string('deputy', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->change();
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('ts_jobname', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->string('ts_foldername')->change();
            $table->string('teacher')->change();
            $table->string('principal')->change();
            $table->string('deputy')->change();
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('ts_jobname')->change();
        });
    }
};
