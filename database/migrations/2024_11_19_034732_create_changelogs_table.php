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
        Schema::create('issue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();  
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_name', 50)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); 
            $table->string('external_issue_name', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();  
            $table->text('issue_description')->nullable();
            $table->text('issue_error_message')->nullable();
            $table->unsignedBigInteger('approval_status_id')->nullable();
            $table->unsignedBigInteger('issue_category_id')->nullable();
            $table->integer('is_proceed_confirm')->nullable();
            $table->foreign('approval_status_id')->references('id')->on('status')->onDelete('cascade');
            $table->foreign('issue_category_id')->references('id')->on('issue_categories')->onDelete('cascade');
        });

        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();        
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ts_jobkey', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); 
            $table->string('keyvalue', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); 
            $table->string('keyorigin', 25)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); 
            $table->text('change_from')->nullable();
            $table->text('change_to')->nullable();
            $table->text('notes')->nullable();        
            $table->unsignedBigInteger('resolved_status_id')->nullable();       
            $table->unsignedBigInteger('issue_id')->nullable();
            $table->dateTime('change_datetime')->nullable();
            $table->dateTime('decision_datetime')->nullable();
            $table->integer('approvalStatus')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_status_id')->references('id')->on('status')->onDelete('cascade');
            $table->foreign('issue_id')->references('id')->on('issues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_categories');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('changelogs');
    }
};
