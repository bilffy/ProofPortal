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
        // Update the user_otps table
        Schema::table('user_otps', function (Blueprint $table) {
            $table->integer('otp_attempts')->default(0)->after('expire_on');
            $table->integer('resend_attempts')->default(0)->after('otp_attempts');
            $table->dateTime('last_resend_at')->nullable()->after('resend_attempts');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the changes
        Schema::table('user_otps', function (Blueprint $table) {
            $table->dropColumn('otp_attempts');
            $table->dropColumn('resend_attempts');
            $table->dropColumn('last_resend_at');
        });
    }
};

 
