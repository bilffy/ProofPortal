<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropColumn(['active_status_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // First, add the new column with correct type
            $table->unsignedBigInteger('active_status_id')->nullable()->after('status');
        });
        
        Schema::table('users', function (Blueprint $table) {
            // Add foreign key constraint
            $table->foreign('active_status_id')
                ->references('id')
                ->on('status')->nullable();

        });
        
        // Populate the new column by matching status name to status.id
        DB::statement('
            UPDATE users
            JOIN status ON users.status = status.status_external_name
            SET users.active_status_id = status.id
        ');

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['active_status_id']);
        });
    }
};
