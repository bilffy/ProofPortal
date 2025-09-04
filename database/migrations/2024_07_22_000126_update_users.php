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
            $table->string('firstname', 25)->nullable()->after('remember_token');
            $table->string('lastname', 25)->nullable()->after('firstname');
            $table->string('username', 25)->after('lastname');
            $table->string('address', 1000)->nullable()->after('username');
            $table->string('suburb', 25)->nullable()->after('address');
            $table->string('postcode', 25)->nullable()->after('suburb');
            $table->string('state', 25)->nullable()->after('postcode');
            $table->string('country', 25)->nullable()->after('state');
            $table->string('contact', 25)->nullable()->after('country');
            $table->integer('active_status_id')->nullable()->after('contact');
            $table->dateTime('activation_date')->nullable()->after('active_status_id');
            $table->dateTime('expiry_date')->nullable()->after('activation_date');
            $table->integer('password_expiry')->nullable()->after('expiry_date');
            $table->dateTime('password_expiry_date')->nullable()->after('password_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'lastname',
                'username',
                'address',
                'suburb',
                'postcode',
                'state',
                'country',
                'contact',
                'active_status_id',
                'activation_date',
                'expiry_date',
                'password_expiry',
                'password_expiry_date'
            ]);
        });
    }
};
