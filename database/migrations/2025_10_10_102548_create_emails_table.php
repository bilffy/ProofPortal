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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generated_from_user_id')->nullable();
            $table->string('alphacode', 25)->nullable();
            $table->string('ts_jobkey', 25)->nullable();
            $table->string('ts_schoolkey', 25)->nullable();
            $table->dateTime('sentdate')->nullable();
            $table->string('email_from', 50)->nullable();
            $table->string('email_to', 50)->nullable();
            $table->string('email_cc', 50)->nullable();
            $table->string('email_bcc', 50)->nullable();
            $table->longText('email_content')->nullable();
            $table->integer('smtp_code')->nullable();
            $table->string('smtp_message', 25)->nullable();
            $table->char('email_token', 36)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->integer('status_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('generated_from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
