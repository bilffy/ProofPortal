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
        DB::table('status')->insert([
            ['status_internal_name' => 'Download Started', 'status_external_name' => 'Download Started', 'created_at' => '2024-11-19 00:12:11'],
            ['status_internal_name' => 'Download Failed', 'status_external_name' => 'Download Failed', 'created_at' => '2024-11-19 00:12:11'],
            ['status_internal_name' => 'Download Email Failed', 'status_external_name' => 'Download Email Failed', 'created_at' => '2024-11-19 00:12:11'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('status')->where('status_internal_name', 'Download Started')->delete();
        DB::table('status')->where('status_internal_name', 'Download Failed')->delete();
        DB::table('status')->where('status_internal_name', 'Download Email Failed')->delete();
    }
};
