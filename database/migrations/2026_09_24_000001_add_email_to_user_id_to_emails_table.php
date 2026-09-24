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
        Schema::table('emails', function (Blueprint $table) {
            // Links an emails row back to the user it was sent to, resolved
            // by matching email_to against users.email. Historical rows only
            // ever stored the raw address string, so this can't be backfilled
            // for existing data automatically (the address on a row may no
            // longer match any current user, or may match a user who has
            // since changed their email) - it gets populated going forward
            // whenever a user is edited (see UserController::update()).
            $table->unsignedBigInteger('email_to_userId')->nullable()->after('email_to');
            $table->foreign('email_to_userId')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropForeign(['email_to_userId']);
            $table->dropColumn('email_to_userId');
        });
    }
};
